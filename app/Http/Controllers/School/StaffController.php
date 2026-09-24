<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\ClassSubject;
use App\Models\Committee;
use App\Models\SchoolClass;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\Audit;
use App\Services\Notifier;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    protected function roleOptions(): array
    {
        $school = current_school();
        $roles = [];
        if ($school->hasPrimary()) {
            $roles = array_merge($roles, User::PRIMARY_STAFF_ROLES);
        }
        if ($school->hasSecondary()) {
            $roles = array_merge($roles, User::SECONDARY_STAFF_ROLES);
        }

        return collect(array_unique($roles))->mapWithKeys(fn ($r) => [$r => User::ROLES[$r]])->all();
    }

    protected function findStaff($id): User
    {
        return User::where('school_id', current_school()->id)->whereIn('role', User::TEACHING_ROLES)->findOrFail($id);
    }

    public function index(Request $request)
    {
        $q = User::with(['teacherProfile', 'committees'])->where('school_id', current_school()->id)->whereIn('role', User::TEACHING_ROLES);
        if ($request->filled('role')) {
            $q->where('role', $request->query('role'));
        }
        if ($s = $request->query('q')) {
            $q->where('name', 'like', "%$s%");
        }
        $year = current_school()->currentYear();

        return view('school.staff.index', [
            'staff' => $q->orderByRaw("FIELD(role, 'FACILITY_ADMIN','DEPUTY_HEAD','DEPUTY_HEAD_ACADEMIC','DEPUTY_HEAD_ADMIN','SECTION_HEAD','HEAD_OF_DEPARTMENT','FORM_MASTER','CLASS_TEACHER','SUBJECT_TEACHER')")->orderBy('name')->get(),
            'roles' => $this->roleOptions(),
            'classOf' => $year ? SchoolClass::with('level')->where('academic_year_id', $year->id)->whereNotNull('class_teacher_id')->get()->keyBy('class_teacher_id') : collect(),
            'loads' => ClassSubject::selectRaw('teacher_id, count(*) as lessons, sum(periods_per_week) as periods')->whereNotNull('teacher_id')->groupBy('teacher_id')->get()->keyBy('teacher_id'),
        ]);
    }

    public function create()
    {
        return view('school.staff.form', [
            'user' => new User(['preferred_channel' => 'SMS']),
            'roles' => $this->roleOptions(),
            'committees' => Committee::orderBy('name')->get(),
        ]);
    }

    protected function rules(?User $user = null): array
    {
        return [
            'name' => 'required|string|max:120',
            'email' => ['nullable', 'required_if:channel,EMAIL', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'required_if:channel,SMS', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user?->id)],
            'national_id' => 'required|string|max:30',
            'gender' => 'required|in:Male,Female',
            'role' => ['required', Rule::in(array_keys($this->roleOptions()))],
            'qualification' => ['required', Rule::in(array_keys(TeacherProfile::QUALIFICATIONS))],
            'employment_number' => 'nullable|string|max:30',
            'specialisation' => 'nullable|string|max:120',
            'channel' => 'required|in:SMS,EMAIL',
            'committees' => 'array',
            'committees.*' => 'integer',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules() + ['credential' => 'required|in:OTP,TEMP_PASSWORD']);

        $user = User::create([
            'school_id' => current_school()->id,
            'name' => $data['name'],
            'email' => $data['email'] ?: null,
            'phone' => $data['phone'] ?: null,
            'national_id' => $data['national_id'],
            'gender' => $data['gender'],
            'role' => $data['role'],
            'status' => 'PENDING_ACTIVATION',
            'preferred_channel' => $data['channel'],
        ]);
        TeacherProfile::create([
            'user_id' => $user->id,
            'qualification' => $data['qualification'],
            'employment_number' => $data['employment_number'] ?? null,
            'specialisation' => $data['specialisation'] ?? null,
        ]);
        $user->committees()->sync(Committee::whereIn('id', $data['committees'] ?? [])->pluck('id'));

        Audit::log('user.invited', 'Invited '.$user->name.' as '.$user->roleLabel(), $user);
        $code = OtpService::issue($user, 'ACTIVATION', $data['credential'], $data['channel']);
        if (config('services.sms.show_codes')) {
            session()->flash('dev_code', $code);
        }

        return redirect()->route('school.staff.index')->with('status', $user->name.' has been invited. Activation details were sent by '.strtolower($data['channel'] === 'SMS' ? 'SMS' : 'email').'.');
    }

    public function edit($id)
    {
        $user = $this->findStaff($id);
        abort_if($user->role === 'FACILITY_ADMIN' && $user->id !== auth()->id(), 403);

        return view('school.staff.form', [
            'user' => $user->load(['teacherProfile', 'committees']),
            'roles' => $user->role === 'FACILITY_ADMIN' ? ['FACILITY_ADMIN' => 'Head Teacher'] : $this->roleOptions(),
            'committees' => Committee::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $this->findStaff($id);
        $rules = $this->rules($user);
        if ($user->role === 'FACILITY_ADMIN') {
            $rules['role'] = 'required|in:FACILITY_ADMIN';
        }
        $data = $request->validate($rules);
        $oldRole = $user->role;

        $user->update([
            'name' => $data['name'], 'email' => $data['email'] ?: null, 'phone' => $data['phone'] ?: null,
            'national_id' => $data['national_id'], 'gender' => $data['gender'], 'role' => $data['role'],
            'preferred_channel' => $data['channel'],
        ]);
        $user->teacherProfile()->updateOrCreate([], [
            'qualification' => $data['qualification'],
            'employment_number' => $data['employment_number'] ?? null,
            'specialisation' => $data['specialisation'] ?? null,
        ]);
        $user->committees()->sync(Committee::whereIn('id', $data['committees'] ?? [])->pluck('id'));

        if ($oldRole !== $user->role) {
            Audit::log('user.role', 'Changed role of '.$user->name.' from '.(User::ROLES[$oldRole] ?? $oldRole).' to '.$user->roleLabel(), $user);
            Notifier::send($user, 'ASSIGNMENT', 'Your role has changed', 'You are now '.$user->roleLabel().' at '.current_school()->name.'.', route('dashboard'));
        } else {
            Audit::log('user.updated', 'Updated profile of '.$user->name, $user);
        }

        return redirect()->route('school.staff.index')->with('status', 'Staff record saved.');
    }

    public function resend(Request $request, $id)
    {
        $user = $this->findStaff($id);
        abort_unless($user->status === 'PENDING_ACTIVATION', 422);
        $code = OtpService::issue($user, 'ACTIVATION', $request->input('kind', 'OTP'));
        Audit::log('user.code_resent', 'Sent a new activation code to '.$user->name, $user);
        if (config('services.sms.show_codes')) {
            session()->flash('dev_code', $code);
        }

        return back()->with('status', 'A new activation code has been sent to '.$user->name.'.');
    }

    public function status(Request $request, $id)
    {
        $user = $this->findStaff($id);
        abort_if($user->id === auth()->id(), 422, 'You cannot suspend your own account.');
        $new = $user->status === 'SUSPENDED' ? ($user->activated_at ? 'ACTIVE' : 'PENDING_ACTIVATION') : 'SUSPENDED';
        $user->update(['status' => $new]);
        Audit::log('user.status', ($new === 'SUSPENDED' ? 'Suspended ' : 'Restored ').$user->name, $user);

        return back()->with('status', $user->name.' is now '.strtolower(label($new)).'.');
    }
}
