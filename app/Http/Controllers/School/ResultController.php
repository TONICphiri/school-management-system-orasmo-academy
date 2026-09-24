<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\ClassResultStatus;
use App\Models\ClassSubject;
use App\Models\ReportComment;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\SubjectResultStatus;
use App\Models\User;
use App\Services\Audit;
use App\Services\Grading;
use App\Services\Notifier;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public const STAGES = [
        'OPEN' => 'Marks being entered',
        'CLASS_REVIEWED' => 'Reviewed by class leader',
        'DEPUTY_APPROVED' => 'Approved by Deputy Head',
        'RELEASED' => 'Released to families',
    ];

    public function canValidate(User $user, ClassSubject $lesson): bool
    {
        $lesson->loadMissing(['subject.department', 'schoolClass.level']);
        if (in_array($user->role, ['FACILITY_ADMIN', 'DEPUTY_HEAD_ACADEMIC'])) {
            return $lesson->schoolClass->isSecondary();
        }
        if ($user->role === 'HEAD_OF_DEPARTMENT') {
            return $lesson->subject->department?->head_id === $user->id;
        }
        if ($user->role === 'SECTION_HEAD') {
            return $lesson->schoolClass->section_id && Section::whereKey($lesson->schoolClass->section_id)->where('head_id', $user->id)->exists();
        }

        return false;
    }

    protected function classStatus(SchoolClass $class)
    {
        return ClassResultStatus::firstOrCreate(
            ['school_class_id' => $class->id, 'term_id' => current_term()->id],
            ['school_id' => $class->school_id, 'stage' => 'OPEN']
        );
    }

    public function index(Request $request)
    {
        $term = current_term();
        $year = current_school()->currentYear();
        $classes = $year ? SchoolClass::with(['level', 'classTeacher', 'classSubjects'])->where('academic_year_id', $year->id)->get()
            ->sortBy(fn ($c) => [$c->level->phase, $c->level->ordinal, $c->stream])->values() : collect();
        $statuses = $term ? SubjectResultStatus::where('term_id', $term->id)->get()->groupBy(fn ($s) => $s->class_subject_id) : collect();

        $rows = $classes->map(function ($class) use ($term, $statuses) {
            $lessonIds = $class->classSubjects->pluck('id');
            $sub = $lessonIds->map(fn ($id) => $statuses[$id][0]->status ?? 'DRAFT');

            return [
                'class' => $class,
                'stage' => $term ? $this->classStatus($class)->stage : 'OPEN',
                'total' => $lessonIds->count(),
                'submitted' => $sub->filter(fn ($s) => in_array($s, ['SUBMITTED', 'VALIDATED']))->count(),
                'validated' => $sub->filter(fn ($s) => $s === 'VALIDATED')->count(),
            ];
        });

        return view('school.results.index', ['term' => $term, 'rows' => $rows, 'stages' => self::STAGES]);
    }

    public function show(Request $request, SchoolClass $class)
    {
        $user = $request->user();
        $term = current_term();
        abort_unless($term, 422);
        $class->load(['level', 'classTeacher', 'classSubjects.subject.department', 'classSubjects.teacher']);
        $allowed = $user->isSchoolLeader() || $class->class_teacher_id === $user->id
            || $class->classSubjects->contains(fn ($cs) => $cs->teacher_id === $user->id || $this->canValidate($user, $cs));
        abort_unless($allowed, 403);

        $status = $this->classStatus($class);
        $subjectStatuses = $class->classSubjects->mapWithKeys(fn ($cs) => [$cs->id => $cs->statusFor($term)]);

        return view('school.results.show', [
            'class' => $class,
            'term' => $term,
            'status' => $status,
            'results' => Grading::classResults($class, $term),
            'subjectStatuses' => $subjectStatuses,
            'comments' => ReportComment::where('term_id', $term->id)->whereIn('student_id', $class->students()->pluck('id'))->get()->keyBy('student_id'),
            'stages' => self::STAGES,
            'user' => $user,
            'canReview' => $class->class_teacher_id === $user->id || $user->role === 'FACILITY_ADMIN',
            'isSecondary' => $class->isSecondary(),
            'showAll' => $user->isSchoolLeader() || $class->class_teacher_id === $user->id,
        ]);
    }

    public function validateSubject(Request $request, SubjectResultStatus $status)
    {
        $lesson = $status->classSubject()->with(['subject', 'schoolClass', 'teacher'])->first();
        abort_unless($this->canValidate($request->user(), $lesson), 403, 'You cannot validate marks for this subject.');
        abort_unless($status->status === 'SUBMITTED', 422, 'Only submitted marks can be validated.');
        $status->update(['status' => 'VALIDATED', 'validated_by' => $request->user()->id, 'validated_at' => now()]);
        Audit::log('marks.validated', 'Validated '.$lesson->subject->name.' marks for '.$lesson->schoolClass->name(), $lesson);

        Notifier::send($lesson->teacher, 'RESULTS', $lesson->subject->name.' marks validated',
            'Your '.$lesson->subject->name.' marks for '.$lesson->schoolClass->name().' were validated by '.$request->user()->name.'.', route('school.marks.show', $lesson));

        $class = $lesson->schoolClass;
        $pending = SubjectResultStatus::where('term_id', $status->term_id)->whereIn('class_subject_id', $class->classSubjects()->pluck('id'))
            ->where('status', '!=', 'VALIDATED')->count();
        if ($pending === 0 && $class->classTeacher) {
            Notifier::send($class->classTeacher, 'RESULTS', 'All subjects validated for '.$class->name(),
                'Every subject for '.$class->name().' is validated. You can now review the class results.', route('school.results.show', $class), 'HIGH');
        }

        return back()->with('status', $lesson->subject->name.' validated.');
    }

    public function returnSubject(Request $request, SubjectResultStatus $status)
    {
        $lesson = $status->classSubject()->with(['subject', 'schoolClass', 'teacher'])->first();
        abort_unless($this->canValidate($request->user(), $lesson) || $request->user()->role === 'FACILITY_ADMIN', 403);
        $data = $request->validate(['remark' => 'required|string|max:250']);
        $status->update(['status' => 'RETURNED', 'remark' => $data['remark'], 'validated_by' => null, 'validated_at' => null]);
        Audit::log('marks.returned', 'Returned '.$lesson->subject->name.' marks for '.$lesson->schoolClass->name().': '.$data['remark'], $lesson);
        Notifier::send($lesson->teacher, 'RESULTS', $lesson->subject->name.' marks returned for correction',
            $request->user()->name.' returned your '.$lesson->subject->name.' marks for '.$lesson->schoolClass->name().'. Note: '.$data['remark'], route('school.marks.show', $lesson), 'HIGH');

        return back()->with('status', 'Marks returned to the subject teacher.');
    }

    public function review(Request $request, SchoolClass $class)
    {
        $user = $request->user();
        abort_unless($class->class_teacher_id === $user->id || $user->role === 'FACILITY_ADMIN', 403, 'Only the class leader can review class results.');
        $class->load('level');
        $status = $this->classStatus($class);
        abort_unless($status->stage === 'OPEN', 422);

        $needed = $class->isSecondary() ? ['VALIDATED'] : ['SUBMITTED', 'VALIDATED'];
        $lessonIds = $class->classSubjects()->pluck('id');
        $ready = SubjectResultStatus::where('term_id', $status->term_id)->whereIn('class_subject_id', $lessonIds)->whereIn('status', $needed)->count();
        $withLearners = $class->classSubjects()->with('subject')->get()->filter(fn ($cs) => $cs->roster()->count() > 0)->count();
        if ($ready < $withLearners) {
            return back()->withErrors(['review' => ($withLearners - $ready).' subjects are not yet '.($class->isSecondary() ? 'validated by the Head of Department' : 'submitted').'.']);
        }

        $status->update(['stage' => 'CLASS_REVIEWED', 'class_reviewed_by' => $user->id, 'class_reviewed_at' => now()]);
        Audit::log('results.class_reviewed', 'Reviewed results for '.$class->name(), $class);

        $next = $class->isSecondary() ? Notifier::schoolRoles($class->school_id, ['DEPUTY_HEAD_ACADEMIC']) : Notifier::schoolRoles($class->school_id, ['FACILITY_ADMIN']);
        Notifier::send($next, 'RESULTS', 'Class results ready for approval',
            $user->name.' reviewed the '.$status->term->label().' results for '.$class->name().'.', route('school.results.show', $class), 'HIGH');

        return back()->with('status', 'Class results reviewed and sent for approval.');
    }

    public function approve(Request $request, SchoolClass $class)
    {
        $class->load('level');
        abort_unless($class->isSecondary(), 422, 'Primary results go straight to the head teacher.');
        $status = $this->classStatus($class);
        abort_unless($status->stage === 'CLASS_REVIEWED', 422);
        $status->update(['stage' => 'DEPUTY_APPROVED', 'deputy_approved_by' => $request->user()->id, 'deputy_approved_at' => now()]);
        Audit::log('results.deputy_approved', 'Approved consolidated results for '.$class->name(), $class);
        Notifier::send(Notifier::schoolRoles($class->school_id, ['FACILITY_ADMIN']), 'RESULTS', 'Results ready for release',
            'The Deputy Head (Academic) approved results for '.$class->name().'.', route('school.results.show', $class), 'HIGH');

        return back()->with('status', 'Results approved and sent to the head teacher for release.');
    }

    public function release(Request $request, SchoolClass $class)
    {
        $class->load('level');
        $status = $this->classStatus($class);
        $needed = $class->isSecondary() ? 'DEPUTY_APPROVED' : 'CLASS_REVIEWED';
        abort_unless($status->stage === $needed, 422, 'These results are not ready for release.');

        $status->update(['stage' => 'RELEASED', 'released_by' => $request->user()->id, 'released_at' => now()]);
        Audit::log('results.released', 'Released '.$status->term->label().' results for '.$class->name().' to learners and parents', $class);

        $results = Grading::classResults($class, $status->term);
        $sent = 0;
        foreach ($class->students()->with(['guardians', 'user'])->where('status', 'ENROLLED')->get() as $student) {
            $r = $results['learners'][$student->id] ?? null;
            $summary = $student->first_name.': average '.num($r['average'] ?? null).', position '.($r['position'] ?? 'n/a').' of '.$results['class_size'];
            if ($class->isSecondary() && isset($r['best_six'])) {
                $summary .= ', best six points '.$r['best_six'];
            }
            $sent += Notifier::send($student->guardians, 'RESULTS', $status->term->label().' report for '.$student->first_name,
                $summary.'. The full report card is available from the school or in the parent portal.', route('school.reports.card', $student), 'NORMAL', ['APP', 'PREFERRED']);
            if ($student->user) {
                Notifier::send($student->user, 'RESULTS', 'Your '.$status->term->label().' report is ready', $summary.'.', route('school.reports.card', $student));
            }
        }

        return back()->with('status', 'Results released. '.$sent.' parents and guardians notified.');
    }

    public function comments(Request $request, SchoolClass $class)
    {
        $user = $request->user();
        $isHead = $user->role === 'FACILITY_ADMIN';
        abort_unless($isHead || $class->class_teacher_id === $user->id, 403);
        $data = $request->validate(['comment' => 'array', 'comment.*' => 'nullable|string|max:300']);
        $ids = $class->students()->pluck('id');
        $field = $isHead && $request->input('as') === 'head' ? 'head_comment' : 'class_teacher_comment';
        foreach ($data['comment'] ?? [] as $studentId => $text) {
            if ($ids->contains((int) $studentId)) {
                ReportComment::updateOrCreate(['student_id' => $studentId, 'term_id' => current_term()->id], [$field => $text, 'school_id' => $class->school_id]);
            }
        }
        Audit::log('results.comments', 'Saved report comments for '.$class->name(), $class);

        return back()->with('status', 'Comments saved.');
    }
}
