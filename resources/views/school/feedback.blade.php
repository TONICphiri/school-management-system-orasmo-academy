@extends('layouts.app')
@section('title', 'Concerns and feedback')
@section('content')
@php $gov = $user->role === 'GOVERNANCE'; @endphp
<div class="page-head"><div><h1>Concerns and feedback</h1><div class="sub">{{ $gov ? 'Raise a concern with the head teacher. If it is not resolved you can escalate it to the District or Division Education Manager.' : 'Concerns raised by SMC, PTA and Board members' }}</div></div></div>
<div class="grid {{ $gov ? 'grid-main' : '' }}">
    <div class="stack">
    @forelse ($items as $f)
        <div class="panel">
            <div class="panel-head">
                <div><h2>{{ $f->subject }}</h2><span class="hint">{{ $f->author?->name }} &middot; {{ $f->created_at->format('j M Y, H:i') }}</span></div>
                <span class="badge {{ status_tone($f->status) }}">{{ label($f->status) }}</span>
            </div>
            <div class="panel-body">
                <p style="white-space:pre-line;margin-top:0">{{ $f->body }}</p>
                @if ($f->response)
                    <div class="alert info" style="margin-bottom:0"><strong>Response from {{ $f->responder?->name ?? 'the school' }}</strong> <span class="small muted">{{ $f->responded_at?->format('j M Y') }}</span><div style="white-space:pre-line">{{ $f->response }}</div></div>
                @endif
                @if ($f->escalated_at)
                    <p class="small muted" style="margin-bottom:0">Escalated to {{ $f->escalatedTo?->name ?? 'the education office' }} on {{ $f->escalated_at->format('j M Y') }}.</p>
                @endif
            </div>
            @if ($user->role === 'FACILITY_ADMIN' && ! in_array($f->status, ['RESOLVED', 'CLOSED', 'ESCALATED']))
                <form method="POST" action="{{ route('school.feedback.respond', $f) }}" class="panel-foot" style="display:block">@csrf
                    <div class="field" style="margin-bottom:.6rem"><label>Your response</label><textarea name="response" rows="3" required></textarea></div>
                    <div class="inline-form" style="justify-content:space-between"><label class="check"><input type="checkbox" name="resolve" value="1"> Mark as resolved</label><button class="btn" type="submit">Send response</button></div>
                </form>
            @elseif ($gov && $f->user_id === $user->id && ! in_array($f->status, ['ESCALATED', 'RESOLVED', 'CLOSED']))
                <div class="panel-foot"><span class="small muted">Not satisfied with the response?</span>
                    <form method="POST" action="{{ route('school.feedback.escalate', $f) }}" data-confirm="Escalate this concern to the education office? The head teacher will be informed.">@csrf<button class="btn danger small" type="submit">{{ icon('flag', 14) }} Escalate</button></form>
                </div>
            @endif
        </div>
    @empty
        <div class="panel"><div class="empty"><strong>No concerns</strong>Nothing has been raised yet.</div></div>
    @endforelse
    {{ $items->links() }}
    </div>
    @if ($gov)
    <div class="panel">
        <div class="panel-head"><h2>Raise a concern</h2></div>
        <form method="POST" action="{{ route('school.feedback.store') }}">@csrf
            <div class="panel-body stack-sm">
                <div class="field"><label>Subject</label><input type="text" name="subject" value="{{ old('subject') }}" required></div>
                <div class="field"><label>Details</label><textarea name="body" rows="6" required>{{ old('body') }}</textarea></div>
            </div>
            <div class="panel-foot"><span class="small muted">The head teacher is notified.</span><button class="btn" type="submit">Send</button></div>
        </form>
    </div>
    @endif
</div>
@endsection
