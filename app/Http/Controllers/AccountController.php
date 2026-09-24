<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function edit(Request $request)
    {
        return view('account.edit', ['user' => $request->user()]);
    }

    public function password(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
        ]);
        $user = $request->user();
        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Your current password is not correct.']);
        }
        $user->update(['password' => $request->input('password')]);
        Audit::log('account.password', $user->name.' changed their password', $user, $user->school_id);

        return back()->with('status', 'Password changed.');
    }

    public function security(Request $request)
    {
        $data = $request->validate([
            'preferred_channel' => 'required|in:SMS,EMAIL',
            'mfa' => 'nullable|boolean',
        ]);
        $user = $request->user();
        $user->update(['preferred_channel' => $data['preferred_channel'], 'mfa_enabled' => $request->boolean('mfa')]);
        Audit::log('account.security', $user->name.' updated sign-in settings', $user, $user->school_id);

        return back()->with('status', 'Security settings saved.');
    }
}
