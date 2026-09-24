<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\FeedbackItem;
use App\Models\User;
use App\Services\Audit;
use App\Services\Notifier;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $q = FeedbackItem::with(['author.governance', 'responder', 'escalatedTo']);
        if ($user->role === 'GOVERNANCE') {
            $q->where('user_id', $user->id);
        }

        return view('school.feedback', ['items' => $q->latest()->paginate(15), 'user' => $user]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['subject' => 'required|string|max:150', 'body' => 'required|string|max:3000']);
        $item = FeedbackItem::create($data + ['user_id' => $request->user()->id, 'status' => 'OPEN']);
        Audit::log('feedback.submitted', $request->user()->name.' raised a concern: '.$item->subject, $item);
        Notifier::send(Notifier::schoolRoles($item->school_id, ['FACILITY_ADMIN']), 'FEEDBACK', 'New concern from '.$request->user()->name,
            $item->subject, route('school.feedback.index'), 'HIGH', ['APP', 'PREFERRED']);

        return back()->with('status', 'Your concern has been sent to the head teacher.');
    }

    public function respond(Request $request, FeedbackItem $item)
    {
        $data = $request->validate(['response' => 'required|string|max:3000', 'resolve' => 'nullable|boolean']);
        $item->update([
            'response' => $data['response'],
            'responded_by' => $request->user()->id,
            'responded_at' => now(),
            'status' => $request->boolean('resolve') ? 'RESOLVED' : 'IN_PROGRESS',
        ]);
        Audit::log('feedback.responded', 'Head teacher responded to "'.$item->subject.'"', $item);
        Notifier::send($item->author, 'FEEDBACK', 'Response to your concern', 'The head teacher responded to "'.$item->subject.'".', route('school.feedback.index'));

        return back()->with('status', 'Response sent.');
    }

    public function escalate(Request $request, FeedbackItem $item)
    {
        abort_unless($item->user_id === $request->user()->id, 403);
        abort_if(in_array($item->status, ['ESCALATED', 'RESOLVED', 'CLOSED']), 422);
        $school = $item->school;
        $supervisor = $school->type === 'PRIMARY'
            ? User::where('role', 'DEM')->where('district_id', $school->district_id)->where('status', 'ACTIVE')->first()
            : User::where('role', 'EDM')->where('division_id', $school->division_id)->where('status', 'ACTIVE')->first();
        if (! $supervisor) {
            return back()->withErrors(['escalate' => 'There is no active '.($school->type === 'PRIMARY' ? 'District Education Manager' : 'Education Division Manager').' for this school yet.']);
        }

        $item->update(['status' => 'ESCALATED', 'escalated_to' => $supervisor->id, 'escalated_at' => now()]);
        Audit::log('feedback.escalated', 'Concern "'.$item->subject.'" escalated to '.$supervisor->roleLabel().' '.$supervisor->name, $item);
        Notifier::send($supervisor, 'FEEDBACK', 'Concern escalated from '.$school->name, $item->subject.'. Raised by '.$item->author->name.'.', route('supervisor.escalations'), 'HIGH', ['APP', 'PREFERRED']);
        Notifier::send(Notifier::schoolRoles($school->id, ['FACILITY_ADMIN']), 'FEEDBACK', 'Concern escalated',
            '"'.$item->subject.'" has been escalated to the '.$supervisor->roleLabel().'.', route('school.feedback.index'), 'HIGH');

        return back()->with('status', 'Concern escalated to '.$supervisor->name.', '.$supervisor->roleLabel().'.');
    }

    public function audit(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'GOVERNANCE') {
            abort_unless($user->governance?->body === 'BOG', 403, 'Audit logs are open to Board of Governors members only.');
        }

        return view('school.audit', [
            'logs' => AuditLog::with('user')->where('school_id', $user->school_id)->latest('created_at')->paginate(30),
        ]);
    }
}
