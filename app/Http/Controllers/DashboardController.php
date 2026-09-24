<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ClassResultStatus;
use App\Models\ClassSubject;
use App\Models\FeedbackItem;
use App\Models\InspectionReport;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SubjectResultStatus;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\Grading;
use App\Services\Stats;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return match (true) {
            $user->isSystemAdmin() => $this->admin(),
            $user->isSupervisor() => $this->supervisor($user),
            in_array($user->role, ['STUDENT', 'PARENT']) => $this->family($user),
            $user->role === 'GOVERNANCE' => $this->governance($user),
            default => $this->school($user),
        };
    }

    protected function admin()
    {
        return view('dashboard.admin', [
            'schoolCount' => School::count(),
            'byStatus' => School::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'byType' => School::selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type'),
            'userCount' => User::where('status', 'ACTIVE')->count(),
            'pendingUsers' => User::where('status', 'PENDING_ACTIVATION')->count(),
            'supervisors' => User::whereIn('role', User::SUPERVISOR_ROLES)->count(),
            'learners' => \App\Models\Student::withoutGlobalScopes()->where('status', 'ENROLLED')->count(),
            'security' => AuditLog::with('user')->where('action', 'like', 'security.%')->latest('created_at')->take(5)->get(),
            'recent' => AuditLog::with(['user', 'school'])->latest('created_at')->take(8)->get(),
            'flagged' => InspectionReport::with(['school', 'supervisor'])->where('flag_follow_up', true)->latest()->take(5)->get(),
            'pendingSchools' => School::with('district')->where('status', 'PENDING_ACTIVATION')->latest()->take(5)->get(),
        ]);
    }

    protected function supervisor(User $user)
    {
        $schools = Stats::jurisdiction($user)->orderBy('name')->get();
        $rows = $schools->map(fn ($s) => ['school' => $s, 'stats' => Stats::school($s)]);

        return view('dashboard.supervisor', [
            'rows' => $rows,
            'totals' => [
                'enrolment' => $rows->sum(fn ($r) => $r['stats']['enrolment']),
                'teachers' => $rows->sum(fn ($r) => $r['stats']['teachers']),
                'attendance' => $rows->pluck('stats.attendance')->filter()->avg(),
                'completion' => $rows->pluck('stats.completion')->filter()->avg(),
                'pass_rate' => $rows->pluck('stats.pass_rate')->filter()->avg(),
            ],
            'flagged' => InspectionReport::with('school')->whereIn('school_id', $schools->pluck('id'))->where('flag_follow_up', true)->latest()->take(5)->get(),
            'escalations' => FeedbackItem::withoutGlobalScopes()->with('school')->where('escalated_to', $user->id)->where('status', 'ESCALATED')->count(),
        ]);
    }

    protected function school(User $user)
    {
        $school = $user->school;
        $term = $school->currentTerm();
        $year = $school->currentYear();
        $data = ['school' => $school, 'term' => $term, 'year' => $year, 'stats' => Stats::school($school)];

        $myLessons = ClassSubject::with(['subject', 'schoolClass.level'])->where('teacher_id', $user->id)->get();
        $data['myLessons'] = $myLessons->map(function ($cs) use ($term) {
            return ['lesson' => $cs, 'status' => $term ? $cs->statusFor($term) : null];
        });
        $data['myClass'] = $year ? SchoolClass::with('level')->withCount('students')
            ->where('academic_year_id', $year->id)->where('class_teacher_id', $user->id)->first() : null;

        $data['today'] = collect();
        $day = now()->dayOfWeekIso;
        if ($term && $day <= 5) {
            $data['today'] = TimetableSlot::with(['classSubject.subject', 'classSubject.schoolClass.level'])
                ->where('term_id', $term->id)->where('day', $day)
                ->whereHas('classSubject', fn ($q) => $q->where('teacher_id', $user->id))
                ->orderBy('period')->get();
        }

        $data['toValidate'] = collect();
        if ($term && in_array($user->role, ['HEAD_OF_DEPARTMENT', 'SECTION_HEAD'])) {
            $data['toValidate'] = SubjectResultStatus::with(['classSubject.subject', 'classSubject.schoolClass.level', 'classSubject.teacher'])
                ->where('term_id', $term->id)->where('status', 'SUBMITTED')
                ->get()->filter(fn ($s) => app(\App\Http\Controllers\School\ResultController::class)->canValidate($user, $s->classSubject));
        }

        if ($user->isSchoolLeader() && $term && $year) {
            $classes = SchoolClass::where('academic_year_id', $year->id)->pluck('id');
            $data['pipeline'] = ClassResultStatus::where('term_id', $term->id)->whereIn('school_class_id', $classes)
                ->selectRaw('stage, count(*) as total')->groupBy('stage')->pluck('total', 'stage');
            $data['classCount'] = $classes->count();
            $data['noClassTeacher'] = SchoolClass::with('level')->where('academic_year_id', $year->id)->whereNull('class_teacher_id')->get();
            $data['noTeacher'] = ClassSubject::whereIn('school_class_id', $classes)->whereNull('teacher_id')->count();
            $data['feedbackOpen'] = FeedbackItem::where('status', 'OPEN')->count();
            $data['recent'] = AuditLog::with('user')->where('school_id', $school->id)->latest('created_at')->take(6)->get();
        }

        return view('dashboard.school', $data);
    }

    protected function family(User $user)
    {
        $students = $user->role === 'STUDENT'
            ? collect([$user->studentRecord])->filter()
            : $user->children()->with('schoolClass.level')->get();
        $term = $user->school?->currentTerm();

        $cards = $students->map(function ($student) use ($term) {
            $status = $term && $student->school_class_id
                ? ClassResultStatus::where('school_class_id', $student->school_class_id)->where('term_id', $term->id)->first()
                : null;
            $released = $status && $status->stage === 'RELEASED';
            $attendance = $term ? $student->attendances()->whereBetween('attended_on', [$term->starts_on, $term->ends_on]) : null;
            $total = $attendance ? (clone $attendance)->count() : 0;

            return [
                'student' => $student,
                'released' => $released,
                'report' => $released ? Grading::studentReport($student, $term) : null,
                'attendance' => $total ? round((clone $attendance)->where('status', 'PRESENT')->count() / $total * 100) : null,
                'absences' => $attendance ? (clone $attendance)->where('status', 'ABSENT')->count() : 0,
            ];
        });

        return view('dashboard.family', ['cards' => $cards, 'term' => $term, 'user' => $user]);
    }

    protected function governance(User $user)
    {
        $school = $user->school;

        return view('dashboard.governance', [
            'school' => $school,
            'stats' => Stats::school($school),
            'membership' => $user->governance,
            'feedback' => FeedbackItem::where('user_id', $user->id)->latest()->take(5)->get(),
            'inspections' => InspectionReport::where('school_id', $school->id)->latest('visit_date')->take(3)->get(),
        ]);
    }
}
