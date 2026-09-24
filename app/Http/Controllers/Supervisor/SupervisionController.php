<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\ClassResultStatus;
use App\Models\ClassSubject;
use App\Models\FeedbackItem;
use App\Models\InspectionReport;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\SubjectResultStatus;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\Audit;
use App\Services\Notifier;
use App\Services\Stats;
use Illuminate\Http\Request;

class SupervisionController extends Controller
{
    public function school(Request $request, School $school)
    {
        abort_unless(Stats::canSupervise($request->user(), $school), 403, 'This school is outside your jurisdiction.');
        $stats = Stats::school($school);
        $term = $stats['term'];
        $year = $school->currentYear();

        $classes = $year ? SchoolClass::withoutGlobalScopes()->with(['level' => fn ($q) => $q->withoutGlobalScopes(), 'classTeacher'])
            ->where('school_id', $school->id)->where('academic_year_id', $year->id)->get()
            ->sortBy(fn ($c) => [$c->level->phase, $c->level->ordinal, $c->stream])->values() : collect();

        $classRows = $classes->map(function ($c) use ($term) {
            $lessonIds = ClassSubject::withoutGlobalScopes()->where('school_class_id', $c->id)->pluck('id');
            $done = $term ? SubjectResultStatus::withoutGlobalScopes()->where('term_id', $term->id)->whereIn('class_subject_id', $lessonIds)->whereIn('status', ['SUBMITTED', 'VALIDATED'])->count() : 0;
            $stage = $term ? ClassResultStatus::withoutGlobalScopes()->where('term_id', $term->id)->where('school_class_id', $c->id)->value('stage') : null;

            return [
                'class' => $c,
                'learners' => Student::withoutGlobalScopes()->where('school_class_id', $c->id)->where('status', 'ENROLLED')->count(),
                'girls' => Student::withoutGlobalScopes()->where('school_class_id', $c->id)->where('status', 'ENROLLED')->where('gender', 'Female')->count(),
                'lessons' => $lessonIds->count(),
                'done' => $done,
                'stage' => $stage ?? 'OPEN',
            ];
        });

        Audit::log('supervision.viewed', $request->user()->roleLabel().' '.$request->user()->name.' reviewed the school dashboard', $school, $school->id);

        return view('supervisor.school', [
            'school' => $school->load(['district', 'division', 'zone']),
            'stats' => $stats,
            'classRows' => $classRows,
            'teachers' => User::with('teacherProfile')->where('school_id', $school->id)->whereIn('role', User::TEACHING_ROLES)->orderBy('name')->get(),
            'qualifications' => TeacherProfile::QUALIFICATIONS,
            'inspections' => InspectionReport::with('supervisor')->where('school_id', $school->id)->latest('visit_date')->get(),
        ]);
    }

    public function escalations(Request $request)
    {
        return view('supervisor.escalations', [
            'items' => FeedbackItem::withoutGlobalScopes()->with(['school', 'author.governance', 'responder'])
                ->where('escalated_to', $request->user()->id)->latest('escalated_at')->paginate(20),
        ]);
    }

    public function closeEscalation(Request $request, $item)
    {
        $item = FeedbackItem::withoutGlobalScopes()->where('escalated_to', $request->user()->id)->findOrFail($item);
        $data = $request->validate(['response' => 'required|string|max:3000']);
        $item->update([
            'status' => 'CLOSED',
            'response' => trim(($item->response ? $item->response."\n\n" : '').$request->user()->roleLabel().': '.$data['response']),
            'responded_at' => now(),
        ]);
        Audit::log('feedback.closed', $request->user()->roleLabel().' closed escalated concern "'.$item->subject.'"', $item, $item->school_id);
        Notifier::send($item->author, 'FEEDBACK', 'Escalated concern closed', $request->user()->name.' has responded to "'.$item->subject.'".', route('school.feedback.index'));
        Notifier::send(Notifier::schoolRoles($item->school_id, ['FACILITY_ADMIN']), 'FEEDBACK', 'Escalated concern closed',
            $request->user()->roleLabel().' responded to "'.$item->subject.'".', route('school.feedback.index'));

        return back()->with('status', 'Response recorded and the school notified.');
    }
}
