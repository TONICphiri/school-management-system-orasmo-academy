<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Division;
use App\Models\User;
use App\Models\Zone;
use App\Services\Audit;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupervisorController extends Controller
{
    public function index()
    {
        return view('admin.supervisors', [
            'supervisors' => User::with(['division', 'district', 'zone'])->whereIn('role', User::SUPERVISOR_ROLES)->orderBy('role')->orderBy('name')->get(),
            'divisions' => Division::orderBy('name')->get(),
            'districts' => District::orderBy('name')->get(),
            'zones' => Zone::with('district')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'role' => ['required', Rule::in(User::SUPERVISOR_ROLES)],
            'email' => 'nullable|required_if:channel,EMAIL|email|unique:users,email',
            'phone' => 'nullable|required_if:channel,SMS|string|max:30|unique:users,phone',
            'channel' => 'required|in:SMS,EMAIL',
            'division_id' => 'required_if:role,EDM|nullable|exists:divisions,id',
            'district_id' => 'required_if:role,DEM|nullable|exists:districts,id',
            'zone_id' => 'required_if:role,PEA|nullable|exists:zones,id',
        ]);

        $zone = $data['role'] === 'PEA' ? Zone::with('district')->find($data['zone_id']) : null;
        $district = $zone?->district ?? ($data['district_id'] ? District::find($data['district_id']) : null);

        $user = User::create([
            'name' => $data['name'],
            'role' => $data['role'],
            'email' => $data['email'] ?: null,
            'phone' => $data['phone'] ?: null,
            'preferred_channel' => $data['channel'],
            'status' => 'PENDING_ACTIVATION',
            'division_id' => $data['role'] === 'EDM' ? $data['division_id'] : $district?->division_id,
            'district_id' => in_array($data['role'], ['DEM', 'PEA']) ? $district?->id : null,
            'zone_id' => $zone?->id,
        ]);

        Audit::log('user.invited', 'Created '.$user->roleLabel().' account for '.$user->name.' covering '.$user->scopeLabel(), $user);
        $code = OtpService::issue($user, 'ACTIVATION');
        if (config('services.sms.show_codes')) {
            session()->flash('dev_code', $code);
        }

        return back()->with('status', $user->roleLabel().' account created. An activation code has been sent.');
    }
}
