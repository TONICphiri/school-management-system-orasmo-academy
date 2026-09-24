<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Audit;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class OnboardingController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        if (! $user->must_change_password && $user->policy_accepted_at) {
            return redirect()->route('dashboard');
        }

        return view('auth.onboarding', ['user' => $user]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
            'accept_policy' => 'accepted',
            'accept_safeguarding' => 'accepted',
            'mfa' => 'nullable|boolean',
        ], [
            'accept_policy.accepted' => 'You must accept the data protection policy to continue.',
            'accept_safeguarding.accepted' => 'You must accept the child safeguarding policy to continue.',
        ]);

        $firstActivation = $user->status === 'PENDING_ACTIVATION';

        $user->update([
            'password' => $request->input('password'),
            'must_change_password' => false,
            'policy_accepted_at' => now(),
            'mfa_enabled' => $request->boolean('mfa'),
            'status' => 'ACTIVE',
            'activated_at' => $user->activated_at ?? now(),
            'last_login_at' => now(),
        ]);

        Audit::log('account.activated', $user->name.' completed activation as '.$user->roleLabel().($user->mfa_enabled ? ' with sign-in codes turned on' : ''), $user, $user->school_id);

        if ($firstActivation) {
            Notifier::send($user, 'ACCOUNT', 'Your account is active',
                'You can now use the MoEST School Management System as '.$user->roleLabel().'.', route('dashboard'));

            if ($user->role === 'FACILITY_ADMIN' && $user->school) {
                if ($user->school->status === 'PENDING_ACTIVATION') {
                    $user->school->update(['status' => 'ACTIVE']);
                }
                Notifier::send(Notifier::systemAdmins(), 'ACCOUNT', 'School activated',
                    $user->name.' activated the head teacher account for '.$user->school->name.'.',
                    route('admin.schools.show', $user->school));
            } elseif ($user->school_id) {
                Notifier::send(Notifier::schoolRoles($user->school_id, ['FACILITY_ADMIN']), 'ACCOUNT', 'New user activated',
                    $user->name.' ('.$user->roleLabel().') has activated their account.', route('school.staff.index'));
            }
        }

        return redirect()->route('dashboard')->with('status', 'Your account is ready.');
    }
}
