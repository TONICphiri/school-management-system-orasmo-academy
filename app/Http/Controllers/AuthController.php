<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Audit;
use App\Services\Notifier;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    protected function findUser(string $identifier): ?User
    {
        $identifier = trim($identifier);

        return User::where('email', $identifier)->orWhere('phone', $identifier)->first();
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'identifier' => 'required|string|max:120',
            'password' => 'required|string',
        ], [], ['identifier' => 'email or phone number']);

        $user = $this->findUser($data['identifier']);

        if ($user && $user->status === 'PENDING_ACTIVATION') {
            return redirect()->route('activate', ['identifier' => $data['identifier']])
                ->with('status', 'This account has not been activated yet. Enter the code sent to you.');
        }

        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            if ($user) {
                $user->increment('failed_logins');
                if ($user->failed_logins === 5) {
                    Notifier::send(Notifier::systemAdmins(), 'SECURITY', 'Repeated failed sign-in attempts',
                        'There have been 5 failed sign-in attempts on the account of '.$user->name.' ('.$user->roleLabel().').',
                        route('admin.audit'), 'HIGH');
                    Audit::log('security.login_failures', 'Five failed sign-in attempts for '.$user->name, $user, $user->school_id);
                }
            }

            return back()->withInput($request->only('identifier'))->withErrors(['login' => 'The email, phone number or password is not correct.']);
        }

        if ($user->status === 'SUSPENDED') {
            return back()->withErrors(['login' => 'This account is suspended. Contact your head teacher or the Ministry help desk.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $user->update(['failed_logins' => 0]);

        if ($user->mfa_enabled) {
            $code = OtpService::issue($user, 'LOGIN');
            $request->session()->put('mfa_pending', true);
            if (config('services.sms.show_codes')) {
                $request->session()->flash('dev_code', $code);
            }

            return redirect()->route('mfa.show');
        }

        $user->update(['last_login_at' => now()]);
        Audit::log('auth.login', $user->name.' signed in', $user, $user->school_id);

        return redirect()->intended(route('dashboard'));
    }

    public function showActivate(Request $request)
    {
        return view('auth.activate', ['identifier' => $request->query('identifier')]);
    }

    public function activate(Request $request)
    {
        $data = $request->validate([
            'identifier' => 'required|string|max:120',
            'code' => 'required|string|max:20',
        ], [], ['identifier' => 'email or phone number', 'code' => 'activation code']);

        $user = $this->findUser($data['identifier']);
        if (! $user || $user->status !== 'PENDING_ACTIVATION') {
            return back()->withInput()->withErrors(['code' => 'We could not find a pending account with those details.']);
        }

        $result = OtpService::verify($user, $data['code'], 'ACTIVATION');
        if ($result !== 'OK') {
            $message = [
                'INVALID' => 'That code is not correct. Check the message and try again.',
                'EXPIRED' => 'That code has expired. Request a new one below.',
                'LOCKED' => 'Too many wrong attempts. The code has been cancelled and the Ministry has been alerted. Request a new code.',
            ][$result];

            return back()->withInput()->withErrors(['code' => $message]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        Audit::log('auth.activation_code', $user->name.' verified the activation code', $user, $user->school_id);

        return redirect()->route('onboarding.show');
    }

    public function resendActivation(Request $request)
    {
        $data = $request->validate(['identifier' => 'required|string|max:120']);
        $user = $this->findUser($data['identifier']);

        if ($user && $user->status === 'PENDING_ACTIVATION') {
            $code = OtpService::issue($user, 'ACTIVATION');
            if (config('services.sms.show_codes')) {
                session()->flash('dev_code', $code);
            }
        }

        return redirect()->route('activate', ['identifier' => $data['identifier']])
            ->with('status', 'If a pending account matches those details, a new code has been sent.');
    }

    public function showMfa()
    {
        if (! session('mfa_pending')) {
            return redirect()->route('dashboard');
        }

        return view('auth.mfa');
    }

    public function verifyMfa(Request $request)
    {
        $request->validate(['code' => 'required|string|max:10']);
        $user = $request->user();
        $result = OtpService::verify($user, $request->input('code'), 'LOGIN');

        if ($result !== 'OK') {
            if ($result === 'LOCKED' || $result === 'EXPIRED') {
                Auth::logout();
                $request->session()->invalidate();

                return redirect()->route('login')->withErrors(['login' => 'The sign-in code expired or was entered wrongly too many times. Sign in again.']);
            }

            return back()->withErrors(['code' => 'That code is not correct.']);
        }

        $request->session()->forget('mfa_pending');
        $user->update(['last_login_at' => now()]);
        Audit::log('auth.login', $user->name.' signed in with a verification code', $user, $user->school_id);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            Audit::log('auth.logout', $user->name.' signed out', $user, $user->school_id);
        }
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
