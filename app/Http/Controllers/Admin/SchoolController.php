<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\District;
use App\Models\Division;
use App\Models\School;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Models\Zone;
use App\Services\Audit;
use App\Services\Notifier;
use App\Services\OtpService;
use App\Services\SchoolSetup;
use App\Services\Stats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SchoolController extends Controller
{
    public function index(Request $request)
    {
        $q = School::with(['district', 'division', 'headTeacher']);
        if ($s = $request->query('q')) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('code', 'like', "%$s%"));
        }
        foreach (['type', 'status', 'category', 'district_id', 'division_id'] as $f) {
            if ($request->filled($f)) {
                $q->where($f, $request->query($f));
            }
        }

        return view('admin.schools.index', [
            'schools' => $q->orderBy('name')->paginate(20)->withQueryString(),
            'divisions' => Division::orderBy('name')->get(),
            'districts' => District::orderBy('name')->get(),
        ]);
    }

    protected function formData(): array
    {
        return [
            'divisions' => Division::orderBy('name')->get(),
            'districts' => District::with('division')->orderBy('name')->get(),
            'zones' => Zone::with('district')->orderBy('name')->get(),
        ];
    }

    public function create()
    {
        $startYear = now()->month >= 7 ? now()->year : now()->year - 1;

        return view('admin.schools.create', $this->formData() + [
            'startYear' => $startYear,
            'terms' => SchoolSetup::defaultTerms($startYear),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'required|string|max:20|unique:schools,code',
            'type' => ['required', Rule::in(array_keys(School::TYPES))],
            'category' => ['required', Rule::in(array_keys(School::CATEGORIES))],
            'structure' => ['required', Rule::in(array_keys(School::STRUCTURES))],
            'status' => ['required', Rule::in(array_keys(School::STATUSES))],
            'division_id' => 'required|exists:divisions,id',
            'district_id' => 'required|exists:districts,id',
            'zone_id' => 'nullable|exists:zones,id',
            'maneb_centre_number' => 'nullable|string|max:20',
            'postal_address' => 'nullable|string|max:200',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
            'year_name' => 'required|string|max:20',
            'terms' => 'required|array|size:3',
            'terms.*.starts_on' => 'required|date',
            'terms.*.ends_on' => 'required|date',
            'terms.*.break_name' => 'nullable|string|max:60',
            'terms.*.break_start' => 'nullable|date',
            'terms.*.break_end' => 'nullable|date',
            'admin_role' => 'required|in:FACILITY_ADMIN,SCHOOL_ADMIN',
            'admin_name' => 'required|string|max:120',
            'admin_email' => 'nullable|required_if:admin_channel,EMAIL|email|max:120|unique:users,email',
            'admin_phone' => 'nullable|required_if:admin_channel,SMS|string|max:30|unique:users,phone',
            'admin_national_id' => 'required|string|max:30',
            'admin_gender' => 'required|in:Male,Female',
            'admin_qualification' => ['nullable', 'required_if:admin_role,FACILITY_ADMIN', Rule::in(array_keys(TeacherProfile::QUALIFICATIONS))],
            'admin_channel' => 'required|in:SMS,EMAIL',
            'admin_credential' => 'required|in:OTP,TEMP_PASSWORD',
        ], [], [
            'admin_name' => 'administrator name', 'admin_email' => 'administrator email',
            'admin_phone' => 'administrator phone', 'admin_national_id' => 'national ID',
        ]);

        $district = District::find($data['district_id']);
        if ((int) $district->division_id !== (int) $data['division_id']) {
            return back()->withInput()->withErrors(['district_id' => 'That district is not in the selected education division.']);
        }
        foreach ([1, 2, 3] as $n) {
            if ($data['terms'][$n]['ends_on'] <= $data['terms'][$n]['starts_on']) {
                return back()->withInput()->withErrors(["terms.$n.ends_on" => "Term $n must end after it starts."]);
            }
        }

        $code = null;
        $school = DB::transaction(function () use ($data, &$code) {
            $school = School::create(collect($data)->only([
                'name', 'code', 'type', 'category', 'structure', 'status', 'division_id', 'district_id',
                'zone_id', 'maneb_centre_number', 'postal_address', 'phone', 'email',
            ])->all());

            SchoolSetup::initialise($school, $data['year_name'], $data['terms']);

            $admin = User::create([
                'school_id' => $school->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'] ?: null,
                'phone' => $data['admin_phone'] ?: null,
                'national_id' => $data['admin_national_id'],
                'gender' => $data['admin_gender'],
                'role' => $data['admin_role'],
                'status' => 'PENDING_ACTIVATION',
                'preferred_channel' => $data['admin_channel'],
            ]);
            if ($data['admin_role'] === 'FACILITY_ADMIN') {
                TeacherProfile::create(['user_id' => $admin->id, 'qualification' => $data['admin_qualification']]);
            }

            Audit::log('school.created', 'Created school '.$school->name.' ('.$school->code.')', $school, $school->id);
            Audit::log('user.invited', 'Invited '.$admin->name.' as '.$admin->roleLabel().' of '.$school->name, $admin, $school->id);

            $code = OtpService::issue($admin, 'ACTIVATION', $data['admin_credential'], $data['admin_channel']);

            return $school;
        });

        if (config('services.sms.show_codes')) {
            session()->flash('dev_code', $code);
        }

        return redirect()->route('admin.schools.show', $school)
            ->with('status', 'School created. An activation '.($data['admin_credential'] === 'OTP' ? 'code' : 'password').' has been sent to the '.strtolower(User::ROLES[$data['admin_role']]).'.');
    }

    public function show(School $school)
    {
        $school->load(['district', 'division', 'zone']);

        return view('admin.schools.show', [
            'school' => $school,
            'stats' => Stats::school($school),
            'head' => $school->headTeacher,
            'sysadmin' => $school->systemAdmin,
            'staff' => User::where('school_id', $school->id)->whereNotIn('role', ['STUDENT', 'PARENT'])->orderBy('role')->get(),
            'years' => \App\Models\AcademicYear::withoutGlobalScopes()->with(['terms' => fn ($q) => $q->withoutGlobalScopes()])->where('school_id', $school->id)->get(),
            'logs' => AuditLog::with('user')->where('school_id', $school->id)->latest('created_at')->take(10)->get(),
        ]);
    }

    public function edit(School $school)
    {
        return view('admin.schools.edit', $this->formData() + ['school' => $school]);
    }

    public function update(Request $request, School $school)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'code' => ['required', 'string', 'max:20', Rule::unique('schools', 'code')->ignore($school->id)],
            'category' => ['required', Rule::in(array_keys(School::CATEGORIES))],
            'division_id' => 'required|exists:divisions,id',
            'district_id' => 'required|exists:districts,id',
            'zone_id' => 'nullable|exists:zones,id',
            'maneb_centre_number' => 'nullable|string|max:20',
            'postal_address' => 'nullable|string|max:200',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120',
        ]);
        $school->update($data);
        Audit::log('school.updated', 'Updated details of '.$school->name, $school, $school->id);

        return redirect()->route('admin.schools.show', $school)->with('status', 'School details saved.');
    }

    public function status(Request $request, School $school)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(School::STATUSES))],
            'reason' => 'nullable|string|max:250',
        ]);
        $old = $school->statusLabel();
        $school->update(['status' => $data['status']]);
        Audit::log('school.status', $school->name.' changed from '.$old.' to '.$school->statusLabel().($data['reason'] ? '. Reason: '.$data['reason'] : ''), $school, $school->id);

        Notifier::send(Notifier::schoolRoles($school->id, \App\Models\User::ADMIN_ROLES), 'ACCOUNT', 'School status changed',
            $school->name.' is now '.$school->statusLabel().'.'.($data['reason'] ? ' Reason: '.$data['reason'] : ''), null, 'HIGH', ['APP', 'PREFERRED']);

        return back()->with('status', 'School status updated to '.$school->statusLabel().'.');
    }

    public function resend(Request $request, School $school)
    {
        $head = $school->systemAdmin;
        abort_unless($head && $head->status === 'PENDING_ACTIVATION', 422, 'The school administrator account is already active.');
        $code = OtpService::issue($head, 'ACTIVATION', $request->input('kind', 'OTP'));
        Audit::log('user.code_resent', 'Sent a new activation code to '.$head->name, $head, $school->id);
        if (config('services.sms.show_codes')) {
            session()->flash('dev_code', $code);
        }

        return back()->with('status', 'A new activation code has been sent to '.$head->name.'.');
    }
}
