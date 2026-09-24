<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\Audit;
use App\Services\Notifier;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $school = current_school();
        $year = $school->currentYear();
        $user = $request->user();

        $classes = $year ? SchoolClass::with(['level', 'section', 'classTeacher'])->withCount('students')
            ->where('academic_year_id', $year->id)->get()
            ->sortBy(fn ($c) => [$c->level->phase, $c->level->ordinal, $c->stream])->values() : collect();
        if (! $this->seesAllClasses($user)) {
            $mine = $user->ownedClassIds()->merge($user->taughtClassIds());
            $classes = $classes->whereIn('id', $mine)->values();
        }

        return view('school.classes.index', [
            'year' => $year,
            'classes' => $classes,
            'levels' => Level::orderBy('phase')->orderBy('ordinal')->get(),
            'teachers' => User::with('teacherProfile')->where('school_id', $school->id)->whereIn('role', User::TEACHING_ROLES)->where('status', '!=', 'SUSPENDED')->orderBy('name')->get(),
            'canManage' => $user->isSchoolAdmin(),
        ]);
    }

    /**
     * Qualification rule: primary class teachers need T2 or above, secondary
     * form masters and subject teachers need a Diploma or above.
     */
    public static function qualificationProblem(?User $teacher, string $phase): ?string
    {
        if (! $teacher) {
            return null;
        }
        $rank = $teacher->teacherProfile?->rank() ?? 0;
        $needed = $phase === 'SECONDARY' ? 'DIPLOMA' : 'T2';
        if ($rank < TeacherProfile::RANK[$needed]) {
            return $teacher->name.' holds '.(TeacherProfile::QUALIFICATIONS[$teacher->teacherProfile?->qualification] ?? 'no recorded qualification')
                .'. '.($phase === 'SECONDARY' ? 'Secondary teaching needs a Diploma in Education or higher.' : 'Primary class teachers need a T2 certificate or higher.');
        }

        return null;
    }

    public function store(Request $request)
    {
        $school = current_school();
        $year = $school->currentYear();
        abort_unless($year, 422, 'Set up the current academic year first.');

        $data = $request->validate([
            'level_id' => 'required|integer',
            'stream' => 'required|string|max:30',
            'capacity' => 'required|integer|min:1|max:200',
            'room' => 'nullable|string|max:30',
            'class_teacher_id' => 'nullable|integer',
        ]);
        $level = Level::findOrFail($data['level_id']);

        if (SchoolClass::where('academic_year_id', $year->id)->where('level_id', $level->id)->where('stream', $data['stream'])->exists()) {
            return back()->withInput()->withErrors(['stream' => $level->name.' '.$data['stream'].' already exists this year.']);
        }
        $teacher = $data['class_teacher_id'] ? $this->teacher($data['class_teacher_id']) : null;
        if ($error = $this->classTeacherProblem($teacher, $level->phase, $year->id)) {
            return back()->withInput()->withErrors(['class_teacher_id' => $error]);
        }

        $section = $level->phase === 'PRIMARY'
            ? Section::where('from_ordinal', '<=', $level->ordinal)->where('to_ordinal', '>=', $level->ordinal)->first() : null;

        $class = SchoolClass::create([
            'academic_year_id' => $year->id,
            'level_id' => $level->id,
            'section_id' => $section?->id,
            'stream' => $data['stream'],
            'capacity' => $data['capacity'],
            'room' => $data['room'],
            'class_teacher_id' => $teacher?->id,
        ]);

        foreach (Subject::where('phase', $level->phase)->get() as $subject) {
            ClassSubject::create([
                'school_class_id' => $class->id,
                'subject_id' => $subject->id,
                'periods_per_week' => in_array($subject->code, ['ENG', 'MAT', 'CHI']) ? 6 : 4,
            ]);
        }

        Audit::log('class.created', 'Created class '.$class->name().' for '.$year->name, $class);
        if ($teacher) {
            $this->notifyClassTeacher($teacher, $class);
        }

        return redirect()->route('school.classes.show', $class)->with('status', $class->name().' created. Assign subject teachers below.');
    }

    protected function classTeacherProblem(?User $teacher, string $phase, int $yearId, ?int $ignoreClass = null): ?string
    {
        if (! $teacher) {
            return null;
        }
        if ($problem = self::qualificationProblem($teacher, $phase)) {
            return $problem;
        }
        $existing = SchoolClass::with('level')->where('academic_year_id', $yearId)->where('class_teacher_id', $teacher->id)
            ->when($ignoreClass, fn ($q) => $q->where('id', '!=', $ignoreClass))->first();
        if ($existing) {
            return $teacher->name.' is already '.($phase === 'SECONDARY' ? 'form master' : 'class teacher').' of '.$existing->name().'. A teacher can lead only one class in a year.';
        }

        return null;
    }

    protected function notifyClassTeacher(User $teacher, SchoolClass $class): void
    {
        $title = $class->isSecondary() ? 'Form master' : 'Class teacher';
        Notifier::send($teacher, 'ASSIGNMENT', 'You are '.strtolower($title).' of '.$class->name(),
            'You are responsible for welfare, attendance and continuous assessment for '.$class->name().'.', route('school.classes.show', $class), 'NORMAL', ['APP', 'PREFERRED']);
    }

    protected function teacher($id): User
    {
        return User::with('teacherProfile')->where('school_id', current_school()->id)->whereIn('role', User::TEACHING_ROLES)->findOrFail($id);
    }

    public function update(Request $request, SchoolClass $class)
    {
        $data = $request->validate([
            'class_teacher_id' => 'nullable|integer',
            'capacity' => 'required|integer|min:1|max:200',
            'room' => 'nullable|string|max:30',
        ]);
        $class->load('level');
        $teacher = $data['class_teacher_id'] ? $this->teacher($data['class_teacher_id']) : null;
        if ($error = $this->classTeacherProblem($teacher, $class->level->phase, $class->academic_year_id, $class->id)) {
            return back()->withErrors(['class_teacher_id' => $error]);
        }
        $changed = $class->class_teacher_id !== $teacher?->id;
        $class->update(['class_teacher_id' => $teacher?->id, 'capacity' => $data['capacity'], 'room' => $data['room']]);
        if ($changed) {
            Audit::log('class.teacher', ($teacher ? $teacher->name.' assigned to lead ' : 'Removed class teacher from ').$class->name(), $class);
            if ($teacher) {
                $this->notifyClassTeacher($teacher, $class);
            }
        }

        return back()->with('status', 'Class details saved.');
    }

    /** Leaders, section heads and heads of department oversee every class. Everyone else sees only classes they own or teach. */
    protected function seesAllClasses(User $user): bool
    {
        return $user->isSchoolLeader() || $user->hasRole('SECTION_HEAD', 'HEAD_OF_DEPARTMENT');
    }

    public function show(Request $request, SchoolClass $class)
    {
        $me = $request->user();
        abort_unless($this->seesAllClasses($me) || $class->class_teacher_id === $me->id || $me->taughtClassIds()->contains($class->id), 403,
            'You can only open classes you own or teach.');
        $class->load(['level', 'section', 'classTeacher', 'classSubjects.subject.department', 'classSubjects.teacher.teacherProfile']);
        $school = current_school();
        $user = $request->user();

        return view('school.classes.show', [
            'class' => $class,
            'students' => $class->students()->with('electives')->get(),
            'lessons' => $class->classSubjects->sortBy(fn ($cs) => [$cs->subject->is_core ? 0 : 1, $cs->subject->name])->values(),
            'electiveCounts' => \Illuminate\Support\Facades\DB::table('student_subjects')->whereIn('class_subject_id', $class->classSubjects->pluck('id'))
                ->selectRaw('class_subject_id, count(*) as total')->groupBy('class_subject_id')->pluck('total', 'class_subject_id'),
            'teachers' => User::with('teacherProfile')->where('school_id', $school->id)->whereIn('role', User::TEACHING_ROLES)->where('status', '!=', 'SUSPENDED')->orderBy('name')->get(),
            'canManage' => $user->isSchoolAdmin(),
            'canSeePii' => $user->isSchoolLeader() || $class->class_teacher_id === $user->id || $user->taughtClassIds()->contains($class->id),
        ]);
    }

    public function assign(Request $request, SchoolClass $class)
    {
        $data = $request->validate([
            'lessons' => 'array',
            'lessons.*.teacher_id' => 'nullable|integer',
            'lessons.*.periods_per_week' => 'required|integer|min:0|max:12',
        ]);
        $class->load('level');
        $errors = [];
        $changes = 0;

        foreach ($data['lessons'] ?? [] as $lessonId => $row) {
            $lesson = ClassSubject::with('subject')->where('school_class_id', $class->id)->findOrFail($lessonId);
            $teacher = $row['teacher_id'] ? $this->teacher($row['teacher_id']) : null;
            if ($teacher && $class->level->phase === 'SECONDARY' && ($problem = self::qualificationProblem($teacher, 'SECONDARY'))) {
                $errors[] = $lesson->subject->name.': '.$problem;
                continue;
            }
            $newTeacher = $lesson->teacher_id !== $teacher?->id;
            $lesson->update(['teacher_id' => $teacher?->id, 'periods_per_week' => $row['periods_per_week']]);
            if ($newTeacher) {
                $changes++;
                Audit::log('lesson.assigned', ($teacher ? $teacher->name.' assigned to teach ' : 'Teacher removed from ').$lesson->subject->name.' in '.$class->name(), $lesson);
                if ($teacher) {
                    Notifier::send($teacher, 'ASSIGNMENT', 'New subject: '.$lesson->subject->name.' in '.$class->name(),
                        'You have been assigned to teach '.$lesson->subject->name.' in '.$class->name().', '.$lesson->periods_per_week.' periods a week.', route('school.marks.show', $lesson));
                }
                if ($teacher && $lesson->subject->department?->head_id && $lesson->subject->department->head_id !== $teacher->id) {
                    Notifier::send(User::find($lesson->subject->department->head_id), 'ASSIGNMENT', 'Teacher assignment to check',
                        $teacher->name.' now teaches '.$lesson->subject->name.' in '.$class->name().'.', route('school.classes.show', $class));
                }
            }
        }

        $message = $changes.' teacher assignment'.($changes === 1 ? '' : 's').' updated.';

        return back()->with('status', $message)->withErrors($errors ? ['lessons' => $errors] : []);
    }
}
