<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\GovernanceMembership;
use App\Models\User;
use App\Services\Audit;
use App\Services\OtpService;
use App\Services\TermSummary;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GovernanceController extends Controller
{
    protected function bodies(): array
    {
        $school = current_school();
        $bodies = ['PTA' => GovernanceMembership::BODIES['PTA']];
        if ($school->hasPrimary()) {
            $bodies = ['SMC' => GovernanceMembership::BODIES['SMC']] + $bodies;
        }
        if ($school->hasSecondary()) {
            $bodies = ['BOG' => GovernanceMembership::BODIES['BOG']] + $bodies;
        }

        return $bodies;
    }

    public function index()
    {
        $members = GovernanceMembership::with('user')->orderBy('body')->orderByDesc('is_voting')->get();

        return view('school.governance', [
            'members' => $members->groupBy('body'),
            'bodies' => $this->bodies(),
            'bogVoting' => $members->where('body', 'BOG')->where('is_voting', true)->count(),
            'bogTotal' => $members->where('body', 'BOG')->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'body' => ['required', Rule::in(array_keys($this->bodies()))],
            'position' => 'required|string|max:60',
            'is_voting' => 'nullable|boolean',
            'phone' => 'nullable|required_if:channel,SMS|string|max:30|unique:users,phone',
            'email' => 'nullable|required_if:channel,EMAIL|email|unique:users,email',
            'channel' => 'required|in:SMS,EMAIL',
            'term_ends' => 'nullable|date',
        ]);

        if ($data['body'] === 'BOG' && GovernanceMembership::where('body', 'BOG')->count() >= 13) {
            return back()->withInput()->withErrors(['body' => 'The Board of Governors already has 13 members, the maximum under the Education Act 2013.']);
        }
        if ($data['body'] === 'BOG' && $request->boolean('is_voting') && GovernanceMembership::where('body', 'BOG')->where('is_voting', true)->count() >= 9) {
            return back()->withInput()->withErrors(['is_voting' => 'The Board already has 9 voting members.']);
        }

        $user = User::create([
            'school_id' => current_school()->id,
            'name' => $data['name'],
            'phone' => $data['phone'] ?: null,
            'email' => $data['email'] ?: null,
            'role' => 'GOVERNANCE',
            'status' => 'PENDING_ACTIVATION',
            'preferred_channel' => $data['channel'],
        ]);
        GovernanceMembership::create([
            'user_id' => $user->id,
            'body' => $data['body'],
            'position' => $data['position'],
            'is_voting' => $request->boolean('is_voting'),
            'term_ends' => $data['term_ends'] ?? null,
        ]);
        Audit::log('user.invited', 'Registered '.$user->name.' as '.$data['position'].' of the '.GovernanceMembership::BODIES[$data['body']], $user);
        $code = OtpService::issue($user, 'ACTIVATION');
        if (config('services.sms.show_codes')) {
            session()->flash('dev_code', $code);
        }

        return back()->with('status', $user->name.' registered. An activation code has been sent.');
    }

    public function sendSummary()
    {
        $count = TermSummary::send(current_school());

        return back()->with('status', 'Term summary sent to '.$count.' governance members.');
    }
}
