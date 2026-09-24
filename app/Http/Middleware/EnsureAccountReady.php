<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAccountReady
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($request->session()->get('mfa_pending')) {
            return redirect()->route('mfa.show');
        }

        if ($user->status === 'SUSPENDED' || ($user->school && $user->school->status === 'SUSPENDED' && ! $user->isSystemAdmin())) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['login' => 'This account or school is suspended. Contact the Ministry help desk.']);
        }

        if ($user->must_change_password || ! $user->policy_accepted_at) {
            return redirect()->route('onboarding.show');
        }

        return $next($request);
    }
}
