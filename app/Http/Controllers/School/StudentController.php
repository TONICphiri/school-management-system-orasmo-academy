<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassSubject;
use App\Models\LearnerSchoolHistory;
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
    protected function allClasses()
    {
        $year = current_school()->currentYear();

        return $year ? SchoolClass::with('level')->where('academic_year_id', $year->id)->get()
            ->sortBy(fn ($c) => [$c->level->phase, $c->level->ordinal, $c->stream])->values() : collect();
    }

    /**
     * Classes a user may register learners into.
     * School administrators and leaders: any class. Class owner: the class they own.
     * Subject teacher: any class they teach a subject in.
     */
    protected function registrationClasses(User $user)
    {
        $all = $this->allClasses();
        if ($user->isSchoolLeader()) {
            return $all;
        }
        $ids = $user->ownedClassIds()->merge($user->taughtClassIds())->unique();

        return $all->whereIn('id', $ids)->values();
    }

    /** Classes whose learner records the user can edit: leaders all, class owners their own class. */
    protected function editableClasses(User $user)
    {
        return $user->isSchoolLeader() ? $this->allClasses() : $this->allClasses()->whereIn('id', $user->ownedClassIds())->values();
    }

    protected function canEdit(User $user, Student $student): bool
    {
        return $user->isSchoolLeader() || $user->ownedClassIds()->contains($student->school_class_id);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $q = Student::with('schoolClass.level');
        $visible = null;

        if (! $user->isSchoolLeader()) {
            $visible = $user->ownedClassIds()->merge($user->taughtClassIds())->unique();
            abort_if($visible->isEmpty(), 403, 'You can only see learners in classes you own or teach.');
            $q->whereIn('school_class_id', $visible);
        }
        if ($request->filled('class_id')) {
            $q->where('school_class_id', $request->query('class_id'));
        }
        if ($s = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('first_name', 'like', "%$s%")->orWhere('last_name', 'like', "%$s%")
                ->orWhere('admission_number', 'like', "%$s%")->orWhere('learner_uid', strtoupper(str_replace(' ', '', $s))));
        }
        if ($request->filled('gender')) {
            $q->where('gender', $request->query('gender'));
        }
        $classes = $this->allClasses();

        return view('school.students.index', [
            'students' => $q->orderBy('last_name')->orderBy('first_name')->paginate(25)->withQueryString(),
            'classes' => $visible ? $classes->whereIn('id', $visible)->values() : $classes,
            'canManage' => $user->isSchoolLeader(),
            'canRegister' => $this->registrationClasses($user)->isNotEmpty(),
        ]);
    }

    /** Suggest the next admission number, for example BC0412/2026/0045. */
    protected function nextAdmissionNumber(): string
    {
        $school = current_school();
        $prefix = $school->code.'/'.now()->year.'/';
        $n = Student::where('admission_number', 'like', $prefix.'%')->count() + 1;
        do {
            $number = $prefix.str_pad((string) $n++, 4, '0', STR_PAD_LEFT);
        } while (Student::where('admission_number', $number)->exists());

        return $number;
    }

    /** Look up a learner by their national Learner ID in any school. Returns [record, error]. */
    protected function findPassport(?string $uid): array
    {
        $uid = strtoupper(str_replace([' ', '/'], '', (string) $uid));
        if ($uid === '') {
            return [null, null];
        }
        if (! Student::validLearnerUid($uid)) {
            return [null, 'That is not a valid Learner ID. Check the number on the learner card and try again.'];
        }
        $record = Student::withoutGlobalScopes()->with('school')->where('learner_uid', $uid)->latest('id')->first();
        if (! $record) {
            return [null, 'No learner with ID '.$uid.' was found.'];
        }
        if ((int) $record->school_id === (int) current_school()->id && $record->status === 'ENROLLED') {
            return [null, $record->fullName().' is already enrolled at this school.'];
        }

        return [$record, null];
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $classes = $this->registrationClasses($user);
        abort_if($classes->isEmpty(), 403, 'Only the class owner or a teacher of the class can register learners.');

        [$source, $lookupError] = $this->findPassport($request->query('learner_id'));
        $student = new Student(['admitted_on' => now(), 'admission_number' => $this->nextAdmissionNumber()]);
        $guardian = null;
        if ($source) {
            $student->fill($source->only(['first_name', 'last_name', 'gender', 'date_of_birth', 'home_village', 'traditional_authority',
                'home_district', 'physical_address', 'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship']));
            $guardian = DB::table('guardian_student')->join('users', 'users.id', '=', 'guardian_student.user_id')
                ->where('student_id', $source->id)->select('users.name', 'users.phone', 'users.email', 'guardian_student.relationship')->first();
        }
        $owned = $user->ownedClassIds();

        return view('school.students.form', [
            'student' => $student,
            'classes' => $classes,
            'defaultClass' => $owned->first(),
            'source' => $source,
            'sourceGuardian' => $guardian,
            'lookupError' => $lookupError,
            'lookup' => $request->query('learner_id'),
        ]);
    }

    protected function rules(?Student $student = null): array
    {
        return [
            'first_name' => 'required|string|max:60',
            'last_name' => 'required|string|max:60',
            'gender' => 'required|in:Male,Female',
            'date_of_birth' => 'required|date|before:today',
            'admission_number' => 'required|string|max:30|unique:students,admission_number,'.($student?->id ?? 'NULL').',id,school_id,'.current_school()->id,
            'school_class_id' => 'required|integer',
            'home_village' => 'nullable|string|max:100',
            'traditional_authority' => 'nullable|string|max:100',
            'home_district' => 'nullable|string|max:60',
            'physical_address' => 'required|string|max:200',
            'emergency_contact_name' => 'nullable|string|max:120',
            'emergency_contact_phone' => 'nullable|required_with:emergency_contact_name|string|max:30',
            'emergency_contact_relationship' => 'nullable|string|max:40',
            'admitted_on' => 'nullable|date',
            'status' => 'nullable|in:ENROLLED,TRANSFERRED,WITHDRAWN,COMPLETED',
        ];
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $school = current_school();
        $data = $request->validate($this->rules() + [
            'guardian_name' => 'required|string|max:120',
            'guardian_phone' => 'required|string|max:30',
            'guardian_email' => 'nullable|email|max:120',
            'guardian_relationship' => 'nullable|string|max:30',
            'previous_school_name' => 'nullable|string|max:150',
            'previous_school_code' => 'nullable|string|max:20',
            'previous_last_class' => 'nullable|string|max:40',
            'previous_year_left' => 'nullable|integer|min:1990|max:'.now()->year,
            'previous_reason' => 'nullable|string|max:150',
            'transfer_from' => 'nullable|integer',
            'create_learner_account' => 'nullable|boolean',
            'learner_email' => 'nullable|required_if:create_learner_account,1|email|unique:users,email',
        ], [
            'physical_address.required' => 'Enter where the learner lives so the school can reach the family.',
            'guardian_name.required' => 'Every learner must be linked to a parent or guardian.',
        ]);

        $class = $this->registrationClasses($user)->firstWhere('id', (int) $data['school_class_id']);
        abort_unless($class, 403, 'You can only register learners into a class you own or teach.');
        if ($class->students()->where('status', 'ENROLLED')->count() >= $class->capacity) {
            return back()->withInput()->withErrors(['school_class_id' => $class->name().' is full ('.$class->capacity.' learners).']);
        }

        $source = null;
        if (! empty($data['transfer_from'])) {
            $source = Student::withoutGlobalScopes()->with(['school', 'schoolClass' => fn ($q) => $q->withoutGlobalScopes()->with(['level' => fn ($l) => $l->withoutGlobalScopes()])])->find($data['transfer_from']);
            abort_unless($source && ! ($source->school_id === $school->id && $source->status === 'ENROLLED'), 422, 'That learner record cannot be transferred.');
        }

        $codes = [];
        $notes = [];
        $student = DB::transaction(function () use ($data, $class, $request, $user, $school, $source, &$codes, &$notes) {
            $student = Student::create(collect($data)->only([
                'first_name', 'last_name', 'gender', 'date_of_birth', 'admission_number', 'school_class_id',
                'home_village', 'traditional_authority', 'home_district', 'physical_address',
                'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship', 'admitted_on',
            ])->all() + [
                'status' => 'ENROLLED',
                'registered_by' => $user->id,
                'learner_uid' => $source?->learner_uid,
                'transferred_from_id' => $source?->id,
            ]);

            if ($source) {
                $leftAs = $source->status === 'COMPLETED' ? 'Completed '.($source->schoolClass?->name() ?? 'studies') : 'Transferred to '.$school->name;
                LearnerSchoolHistory::create([
                    'student_id' => $student->id,
                    'school_name' => $source->school->name,
                    'school_code' => $source->school->code,
                    'last_class' => $source->schoolClass?->name(),
                    'year_left' => now()->year,
                    'reason' => $leftAs,
                ]);
                if ($source->status === 'ENROLLED') {
                    Student::withoutGlobalScopes()->whereKey($source->id)->update(['status' => 'TRANSFERRED', 'updated_at' => now()]);
                }
                Audit::log('student.transferred_out', $source->fullName().' ('.$source->learner_uid.') was admitted at '.$school->name, $source, $source->school_id);
                Notifier::send(Notifier::schoolRoles($source->school_id, User::ADMIN_ROLES), 'ACCOUNT', 'Learner transferred out',
                    $source->fullName().' (Learner ID '.$source->learner_uid.') has been admitted at '.$school->name.' in '.$class->name().'. The record at your school is now marked as transferred.');
            }

            if (! empty($data['previous_school_name'])) {
                LearnerSchoolHistory::create([
                    'student_id' => $student->id,
                    'school_name' => $data['previous_school_name'],
                    'school_code' => $data['previous_school_code'] ?? null,
                    'last_class' => $data['previous_last_class'] ?? null,
                    'year_left' => $data['previous_year_left'] ?? null,
                    'reason' => $data['previous_reason'] ?? null,
                ]);
            }

            if ($request->boolean('create_learner_account')) {
                $account = User::create([
                    'school_id' => $student->school_id, 'name' => $student->fullName(), 'email' => $data['learner_email'],
                    'role' => 'STUDENT', 'status' => 'PENDING_ACTIVATION', 'preferred_channel' => 'EMAIL', 'gender' => $student->gender,
                ]);
                $student->update(['user_id' => $account->id]);
                $codes[] = $account->name.': '.OtpService::issue($account, 'ACTIVATION');
            }

            $guardian = User::where('phone', $data['guardian_phone'])->first();
            if ($guardian && $guardian->role !== 'PARENT') {
                abort(422, 'That phone number belongs to a staff or governance account. Use a different number for the guardian.');
            }
            if ($guardian && (int) $guardian->school_id !== (int) $student->school_id) {
                // A parent whose children have all left the old school moves with the learner.
                $stillThere = $guardian->children()->withoutGlobalScopes()->where('students.school_id', $guardian->school_id)->where('status', 'ENROLLED')->exists();
                if (! $stillThere) {
                    $guardian->update(['school_id' => $student->school_id]);
                } else {
                    $notes[] = $guardian->name.' still has children at another school, so their parent account stays there. The link to this learner has been recorded.';
                }
            }
            if (! $guardian) {
                $guardian = User::create([
                    'school_id' => $student->school_id, 'name' => $data['guardian_name'], 'phone' => $data['guardian_phone'],
                    'email' => ($data['guardian_email'] ?? null) ?: null, 'role' => 'PARENT', 'status' => 'PENDING_ACTIVATION', 'preferred_channel' => 'SMS',
                ]);
                $codes[] = $guardian->name.': '.OtpService::issue($guardian, 'ACTIVATION', 'OTP', 'SMS');
            } else {
                Notifier::send($guardian, 'ACCOUNT', 'Learner linked to your account',
                    $student->fullName().' in '.$class->name().' at '.$student->school->name.' has been linked to your parent account. Learner ID '.$student->learner_uid.'.', route('dashboard'), 'NORMAL', ['APP', 'SMS']);
            }
            $student->guardians()->syncWithoutDetaching([$guardian->id => ['relationship' => ($data['guardian_relationship'] ?? null) ?: 'Parent']]);

            Audit::log($source ? 'student.transferred_in' : 'student.enrolled',
                ($source ? 'Admitted transferring learner ' : 'Registered ').$student->fullName().' (Learner ID '.$student->learner_uid.') in '.$class->name(), $student);

            if ((int) $class->class_teacher_id !== (int) $user->id && $class->class_teacher_id) {
                Notifier::send(User::find($class->class_teacher_id), 'ACCOUNT', 'New learner in '.$class->name(),
                    $user->name.' registered '.$student->fullName().' into your class.', route('school.students.show', $student));
            }

            return $student;
        });

        if ($codes && config('services.sms.show_codes')) {
            session()->flash('dev_code', implode(' | ', $codes));
        }
        foreach ($notes as $note) {
            session()->flash('warning', $note);
        }

        return redirect()->route('school.students.show', $student)
            ->with('status', $student->fullName().' registered in '.$class->name().'. Learner ID '.$student->learner_uid.'.');
    }

    public function show(Request $request, Student $student)
    {
        $user = $request->user();
        abort_unless($user->school_id === $student->school_id && $user->canSeeStudentPii($student), 403, 'You do not have access to this learner record.');
        $student->load(['schoolClass.level', 'schoolClass.classTeacher', 'guardians', 'electives.subject', 'user', 'histories', 'registeredBy']);
        $term = current_term();
        $attendance = $term ? Attendance::where('student_id', $student->id)->whereBetween('attended_on', [$term->starts_on, $term->ends_on])
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status') : collect();

        $electiveOptions = $student->schoolClass && $student->schoolClass->isSecondary()
            ? ClassSubject::with('subject')->where('school_class_id', $student->school_class_id)
                ->whereHas('subject', fn ($q) => $q->where('is_core', false))->get() : collect();

        return view('school.students.show', [
            'student' => $student,
            'term' => $term,
            'attendance' => $attendance,
            'electiveOptions' => $electiveOptions,
            'passport' => $student->passportRecords(),
            'canEdit' => $this->canEdit($user, $student),
        ]);
    }

    public function card(Request $request, Student $student)
    {
        $user = $request->user();
        abort_unless($user->school_id === $student->school_id && $user->canSeeStudentPii($student), 403, 'You do not have access to this learner record.');
        Audit::log('student.card', 'Printed the learner card of '.$student->fullName(), $student);

        return view('school.students.card', ['student' => $student->load(['schoolClass.level', 'school', 'guardians'])]);
    }

    public function edit(Request $request, Student $student)
    {
        abort_unless($this->canEdit($request->user(), $student), 403, 'Only the class owner or school administration can edit this record.');

        return view('school.students.form', ['student' => $student, 'classes' => $this->editableClasses($request->user())]);
    }

    public function update(Request $request, Student $student)
    {
        $user = $request->user();
        abort_unless($this->canEdit($user, $student), 403, 'Only the class owner or school administration can edit this record.');
        $data = $request->validate($this->rules($student));
        abort_unless($this->editableClasses($user)->contains('id', (int) $data['school_class_id']), 403, 'You cannot move a learner into that class.');
        $data['status'] = $data['status'] ?? $student->status;
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

    public function addHistory(Request $request, Student $student)
    {
        abort_unless($this->canEdit($request->user(), $student), 403);
        $data = $request->validate([
            'school_name' => 'required|string|max:150',
            'school_code' => 'nullable|string|max:20',
            'last_class' => 'nullable|string|max:40',
            'year_left' => 'nullable|integer|min:1990|max:'.now()->year,
            'reason' => 'nullable|string|max:150',
        ]);
        $student->histories()->create($data);
        Audit::log('student.history', 'Added previous school '.$data['school_name'].' to the record of '.$student->fullName(), $student);

        return back()->with('status', 'Previous school added.');
    }

    public function electives(Request $request, Student $student)
    {
        abort_unless($this->canEdit($request->user(), $student), 403, 'Only the form master or school administration can set electives.');
        $data = $request->validate(['electives' => 'array', 'electives.*' => 'integer']);
        $allowed = ClassSubject::where('school_class_id', $student->school_class_id)
            ->whereHas('subject', fn ($q) => $q->where('is_core', false))->pluck('id');
        $chosen = collect($data['electives'] ?? [])->map(fn ($v) => (int) $v)->intersect($allowed)->values();
        $student->electives()->sync($chosen);
        Audit::log('student.electives', 'Set '.$chosen->count().' elective subjects for '.$student->fullName(), $student);

        return back()->with('status', 'Elective subjects saved.');
    }
}
