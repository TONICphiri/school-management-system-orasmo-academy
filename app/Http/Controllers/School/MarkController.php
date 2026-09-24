<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Models\Mark;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\Audit;
use App\Services\Grading;
use App\Services\Notifier;
use Illuminate\Http\Request;

class MarkController extends Controller
{
    /**
     * EXAM marks: the assigned subject teacher. CA marks: the subject teacher
     * or, in primary, the class teacher of that class.
     */
    public static function canEnter(User $user, ClassSubject $lesson, string $kind): bool
    {
        if ($lesson->teacher_id === $user->id) {
            return true;
        }
        $lesson->loadMissing('schoolClass.level');

        return $kind === 'CA' && $lesson->schoolClass->level->phase === 'PRIMARY' && $lesson->schoolClass->class_teacher_id === $user->id;
    }

    protected function canView(User $user, ClassSubject $lesson): bool
    {
        return $user->isSchoolLeader() || self::canEnter($user, $lesson, 'CA')
            || app(ResultController::class)->canValidate($user, $lesson)
            || $lesson->schoolClass->class_teacher_id === $user->id;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $term = current_term();
        $year = current_school()->currentYear();
        $classIds = $year ? SchoolClass::where('academic_year_id', $year->id)->pluck('id') : collect();

        $q = ClassSubject::with(['subject', 'schoolClass.level', 'teacher'])->whereIn('school_class_id', $classIds);
        if (! $user->isSchoolLeader() || $request->query('mine')) {
            $own = SchoolClass::where('class_teacher_id', $user->id)->whereHas('level', fn ($l) => $l->where('phase', 'PRIMARY'))->pluck('id');
            $q->where(fn ($w) => $w->where('teacher_id', $user->id)->orWhereIn('school_class_id', $own));
        }
        $lessons = $q->get()->sortBy(fn ($l) => [$l->schoolClass->level->phase, $l->schoolClass->level->ordinal, $l->schoolClass->stream, $l->subject->name])->values();

        return view('school.marks.index', [
            'term' => $term,
            'lessons' => $lessons->map(fn ($l) => ['lesson' => $l, 'status' => $term ? $l->statusFor($term) : null,
                'assessments' => $term ? $l->assessments()->where('term_id', $term->id)->count() : 0]),
        ]);
    }

    public function show(Request $request, ClassSubject $lesson)
    {
        $lesson->load(['subject', 'schoolClass.level', 'teacher']);
        abort_unless($this->canView($request->user(), $lesson), 403);
        $term = current_term();
        abort_unless($term, 422, 'There is no current term.');

        return view('school.marks.show', [
            'lesson' => $lesson,
            'term' => $term,
            'status' => $lesson->statusFor($term),
            'assessments' => $lesson->assessments()->withCount(['marks' => fn ($q) => $q->where(fn ($w) => $w->whereNotNull('score')->orWhere('absent', true))])->where('term_id', $term->id)->orderBy('kind')->orderBy('held_on')->get(),
            'scores' => Grading::subjectScores($lesson, $term),
            'rosterCount' => $lesson->roster()->count(),
            'canCa' => self::canEnter($request->user(), $lesson, 'CA'),
            'canExam' => self::canEnter($request->user(), $lesson, 'EXAM'),
        ]);
    }

    public function storeAssessment(Request $request, ClassSubject $lesson)
    {
        $data = $request->validate([
            'kind' => 'required|in:CA,EXAM',
            'title' => 'required|string|max:80',
            'max_score' => 'required|integer|min:1|max:500',
            'held_on' => 'nullable|date',
            'language' => 'required|in:English,Chichewa,Local language',
        ]);
        abort_unless(self::canEnter($request->user(), $lesson, $data['kind']), 403, 'You cannot add this kind of assessment for this subject.');
        $term = current_term();
        $status = $lesson->statusFor($term);
        if (in_array($status->status, ['SUBMITTED', 'VALIDATED'])) {
            return back()->withErrors(['kind' => 'Marks for this term have been submitted. Ask for them to be returned before adding assessments.']);
        }
        if ($data['kind'] === 'EXAM' && $lesson->assessments()->where('term_id', $term->id)->where('kind', 'EXAM')->exists()) {
            return back()->withErrors(['kind' => 'This subject already has an end of term examination for '.$term->label().'.']);
        }

        $assessment = Assessment::create($data + ['class_subject_id' => $lesson->id, 'term_id' => $term->id, 'created_by' => $request->user()->id]);
        Audit::log('assessment.created', 'Created '.($data['kind'] === 'CA' ? 'continuous assessment' : 'examination').' "'.$assessment->title.'" for '.$lesson->subject->name.' in '.$lesson->schoolClass->name(), $assessment);

        return redirect()->route('school.marks.sheet', $assessment);
    }

    public function sheet(Request $request, Assessment $assessment)
    {
        $lesson = $assessment->classSubject()->with(['subject', 'schoolClass.level'])->first();
        abort_unless($this->canView($request->user(), $lesson), 403);
        $status = $lesson->statusFor($assessment->term);

        return view('school.marks.sheet', [
            'assessment' => $assessment,
            'lesson' => $lesson,
            'students' => $lesson->roster(),
            'marks' => $assessment->marks()->with('enteredBy')->get()->keyBy('student_id'),
            'editable' => self::canEnter($request->user(), $lesson, $assessment->kind) && in_array($status->status, ['DRAFT', 'RETURNED']),
            'status' => $status,
        ]);
    }

    public function saveSheet(Request $request, Assessment $assessment)
    {
        $lesson = $assessment->classSubject()->with(['subject', 'schoolClass'])->first();
        abort_unless(self::canEnter($request->user(), $lesson, $assessment->kind), 403);
        $status = $lesson->statusFor($assessment->term);
        abort_unless(in_array($status->status, ['DRAFT', 'RETURNED']), 422, 'These marks are locked.');

        $data = $request->validate([
            'score' => 'array',
            'score.*' => 'nullable|numeric|min:0|max:'.$assessment->max_score,
            'absent' => 'array',
        ], ['score.*.max' => 'A mark cannot be more than '.$assessment->max_score.'.']);

        $roster = $lesson->roster()->pluck('id');
        $changed = 0;
        foreach ($roster as $studentId) {
            $score = $data['score'][$studentId] ?? null;
            $absent = isset($data['absent'][$studentId]);
            $mark = Mark::firstOrNew(['assessment_id' => $assessment->id, 'student_id' => $studentId]);
            $newScore = $absent ? null : ($score === null || $score === '' ? null : round((float) $score, 2));
            if (! $mark->exists && $newScore === null && ! $absent) {
                continue;
            }
            $oldScore = $mark->exists && $mark->score !== null ? (float) $mark->score : null;
            if ($mark->exists && $oldScore === $newScore && (bool) $mark->absent === $absent) {
                continue;
            }
            $mark->fill([
                'school_id' => $assessment->school_id,
                'score' => $newScore,
                'absent' => $absent,
                'entered_by' => $request->user()->id,
                'entered_at' => now(),
            ])->save();
            $changed++;
        }

        Audit::log('marks.entered', 'Saved '.$changed.' marks for "'.$assessment->title.'", '.$lesson->subject->name.' '.$lesson->schoolClass->name(), $assessment);

        return back()->with('status', $changed.' mark'.($changed === 1 ? '' : 's').' saved.');
    }

    public function submit(Request $request, ClassSubject $lesson)
    {
        abort_unless($lesson->teacher_id === $request->user()->id || $request->user()->role === 'FACILITY_ADMIN', 403, 'Only the subject teacher can submit these marks.');
        $lesson->load(['subject.department', 'schoolClass.level']);
        $term = current_term();
        $status = $lesson->statusFor($term);
        abort_unless(in_array($status->status, ['DRAFT', 'RETURNED']), 422);

        $assessments = $lesson->assessments()->where('term_id', $term->id)->get();
        if (! $assessments->where('kind', 'EXAM')->count()) {
            return back()->withErrors(['submit' => 'Add and fill the end of term examination before submitting.']);
        }
        $roster = $lesson->roster()->count();
        $missing = 0;
        foreach ($assessments as $a) {
            $missing += max(0, $roster - $a->marks()->where(fn ($w) => $w->whereNotNull('score')->orWhere('absent', true))->count());
        }
        if ($missing > 0 && ! $request->boolean('confirm_missing')) {
            return back()->withErrors(['submit' => $missing.' marks are still blank. Mark learners as absent, fill the marks, or tick the box to submit anyway.']);
        }

        $status->update(['status' => 'SUBMITTED', 'submitted_by' => $request->user()->id, 'submitted_at' => now(), 'remark' => null]);
        Audit::log('marks.submitted', 'Submitted '.$lesson->subject->name.' marks for '.$lesson->schoolClass->name().', '.$term->label(), $lesson);

        $class = $lesson->schoolClass;
        if ($class->isSecondary()) {
            $hod = $lesson->subject->department?->head_id ? User::find($lesson->subject->department->head_id) : null;
            Notifier::send($hod ?? Notifier::schoolRoles($class->school_id, ['DEPUTY_HEAD_ACADEMIC']), 'RESULTS', 'Marks waiting for validation',
                $lesson->subject->name.' marks for '.$class->name().' were submitted by '.$request->user()->name.'.', route('school.results.show', $class));
        } else {
            Notifier::send($class->classTeacher, 'RESULTS', 'Subject marks submitted',
                $lesson->subject->name.' marks for '.$class->name().' are ready for your review.', route('school.results.show', $class));
        }

        return back()->with('status', 'Marks submitted for review.');
    }
}
