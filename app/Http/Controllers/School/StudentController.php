<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\Audit;
use App\Services\Notifier;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    protected function classes()
    {
        $year = current_school()->currentYear();

        return $year ? SchoolClass::with('level')->where('academic_year_id', $year->id)->get()
            ->sortBy(fn ($c) => [$c->level->phase, $c->level->ordinal, $c->stream])->values() : collect();
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $q = Student::with('schoolClass.level');

        if (! $user->isSchoolLeader()) {
            $mine = SchoolClass::where('class_teacher_id', $user->id)->pluck('id');
            abort_if($mine->isEmpty(), 403, 'Only class teachers, form masters and school leaders can open learner records.');
            $q->whereIn('school_class_id', $mine);
        }
        if ($request->filled('class_id')) {
            $q->where('school_class_id', $request->query('class_id'));
        }
        if ($s = $request->query('q')) {
            $q->where(fn ($w) => $w->where('first_name', 'like', "%$s%")->orWhere('last_name', 'like', "%$s%")->orWhere('admission_number', 'like', "%$s%"));
        }
        if ($request->filled('gender')) {
            $q->where('gender', $request->query('gender'));
        }

        return view('school.students.index', [
            'students' => $q->orderBy('last_name')->orderBy('first_name')->paginate(25)->withQueryString(),
            'classes' => $this->classes(),
            'canManage' => $user->isSchoolLeader(),
        ]);
    }

    public function create()
    {
        return view('school.students.form', ['student' => new Student(['admitted_on' => now()]), 'classes' => $this->classes()]);
    }

    protected function rules(?Student $student = null): array
    {
        return [
            'first_name' => 'required|string|max:60',
            'last_name' => 'required|string|max:60',
            'gender' => 'required|in:Male,Female',
            'date_of_birth' => 'nullable|date|before:today',
            'admission_number' => 'required|string|max:30|unique:students,admission_number,'.($student?->id ?? 'NULL').',id,school_id,'.current_school()->id,
            'school_class_id' => 'required|integer',
            'home_village' => 'nullable|string|max:100',
            'traditional_authority' => 'nullable|string|max:100',
            'home_district' => 'nullable|string|max:60',
            'admitted_on' => 'nullable|date',
            'status' => 'nullable|in:ENROLLED,TRANSFERRED,WITHDRAWN,COMPLETED',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules() + [
            'guardian_name' => 'nullable|string|max:120',
            'guardian_phone' => 'nullable|required_with:guardian_name|string|max:30',
            'guardian_email' => 'nullable|email|max:120',
            'guardian_relationship' => 'nullable|string|max:30',
            'create_learner_account' => 'nullable|boolean',
            'learner_email' => 'nullable|required_if:create_learner_account,1|email|unique:users,email',
        ]);
        $class = SchoolClass::with('level')->findOrFail($data['school_class_id']);
        if ($class->students()->count() >= $class->capacity) {
            return back()->withInput()->withErrors(['school_class_id' => $class->name().' is full ('.$class->capacity.' learners).']);
        }

        $codes = [];
        $student = DB::transaction(function () use ($data, $class, $request, &$codes) {
            $student = Student::create(collect($data)->only([
                'first_name', 'last_name', 'gender', 'date_of_birth', 'admission_number', 'school_class_id',
                'home_village', 'traditional_authority', 'home_district', 'admitted_on',
            ])->all() + ['status' => 'ENROLLED']);

            if ($request->boolean('create_learner_account')) {
                $account = User::create([
                    'school_id' => $student->school_id, 'name' => $student->fullName(), 'email' => $data['learner_email'],
                    'role' => 'STUDENT', 'status' => 'PENDING_ACTIVATION', 'preferred_channel' => 'EMAIL', 'gender' => $student->gender,
                ]);
                $student->update(['user_id' => $account->id]);
                $codes[] = $account->name.': '.OtpService::issue($account, 'ACTIVATION');
            }

            if (! empty($data['guardian_name'])) {
                $guardian = User::where('phone', $data['guardian_phone'])->first();
                if ($guardian && ($guardian->role !== 'PARENT' || $guardian->school_id !== $student->school_id)) {
                    abort(422, 'That phone number belongs to another type of account.');
                }
                if (! $guardian) {
                    $guardian = User::create([
                        'school_id' => $student->school_id, 'name' => $data['guardian_name'], 'phone' => $data['guardian_phone'],
                        'email' => $data['guardian_email'] ?: null, 'role' => 'PARENT', 'status' => 'PENDING_ACTIVATION', 'preferred_channel' => 'SMS',
                    ]);
                    $codes[] = $guardian->name.': '.OtpService::issue($guardian, 'ACTIVATION', 'OTP', 'SMS');
                } else {
                    Notifier::send($guardian, 'ACCOUNT', 'Learner linked to your account',
                        $student->fullName().' in '.$class->name().' has been linked to your parent account.', route('dashboard'));
                }
                $student->guardians()->syncWithoutDetaching([$guardian->id => ['relationship' => $data['guardian_relationship'] ?: 'Parent']]);
            }

            Audit::log('student.enrolled', 'Enrolled '.$student->fullName().' ('.$student->admission_number.') in '.$class->name(), $student);

            return $student;
        });

        if ($codes && config('services.sms.show_codes')) {
            session()->flash('dev_code', implode(' | ', $codes));
        }

        return redirect()->route('school.students.show', $student)->with('status', $student->fullName().' enrolled in '.$class->name().'.');
    }

    public function show(Request $request, Student $student)
    {
        $user = $request->user();
        abort_unless($user->school_id === $student->school_id && $user->canSeeStudentPii($student), 403, 'You do not have access to this learner record.');
        $student->load(['schoolClass.level', 'schoolClass.classTeacher', 'guardians', 'electives.subject', 'user']);
        $term = current_term();
        $attendance = $term ? Attendance::where('student_id', $student->id)->whereBetween('attended_on', [$term->starts_on, $term->ends_on])
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status') : collect();

        $electiveOptions = $student->schoolClass && $student->schoolClass->isSecondary()
            ? ClassSubject::with('subject')->where('school_class_id', $student->school_class_id)
                ->whereHas('subject', fn ($q) => $q->where('is_core', false))->get() : collect();

        return view('school.students.show', compact('student', 'term', 'attendance', 'electiveOptions'));
    }

    public function edit(Student $student)
    {
        return view('school.students.form', ['student' => $student, 'classes' => $this->classes()]);
    }

    public function update(Request $request, Student $student)
    {
        $data = $request->validate($this->rules($student));
        $oldClass = $student->school_class_id;
        $student->update($data);
        if ((int) $oldClass !== (int) $student->school_class_id) {
            $student->electives()->detach();
            Audit::log('student.moved', 'Moved '.$student->fullName().' to '.$student->schoolClass->name(), $student);
        } else {
            Audit::log('student.updated', 'Updated record of '.$student->fullName(), $student);
        }

        return redirect()->route('school.students.show', $student)->with('status', 'Learner record saved.');
    }

    public function electives(Request $request, Student $student)
    {
        $data = $request->validate(['electives' => 'array', 'electives.*' => 'integer']);
        $allowed = ClassSubject::where('school_class_id', $student->school_class_id)
            ->whereHas('subject', fn ($q) => $q->where('is_core', false))->pluck('id');
        $chosen = collect($data['electives'] ?? [])->map(fn ($v) => (int) $v)->intersect($allowed)->values();
        $student->electives()->sync($chosen);
        Audit::log('student.electives', 'Set '.$chosen->count().' elective subjects for '.$student->fullName(), $student);

        return back()->with('status', 'Elective subjects saved.');
    }
}
