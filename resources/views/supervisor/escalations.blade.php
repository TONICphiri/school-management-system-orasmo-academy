@extends('layouts.app')
@section('title', 'Escalated concerns')
@section('content')
<div class="page-head"><div><h1>Escalated concerns</h1><div class="sub">Concerns from SMC, PTA and Board members that were not resolved at school level</div></div></div>
@forelse ($items as $f)
    <div class="panel">
        <div class="panel-head">
            <div><h2>{{ $f->subject }}</h2><span class="hint">{{ $f->school->name }} &middot; {{ $f->author?->name }}{{ $f->author?->governance ? ', '.$f->author->governance->position.' ('.$f->author->governance->body.')' : '' }} &middot; escalated {{ $f->escalated_at?->format('j M Y') }}</span></div>
            <span class="badge {{ status_tone($f->status) }}">{{ label($f->status) }}</span>
        </div>
        <div class="panel-body">
            <p class="whitespace-pre-line mt-0">{{ $f->body }}</p>
            @if($f->response)<div class="alert info mb-0"><strong>Responses</strong><div class="whitespace-pre-line">{{ $f->response }}</div></div>@endif
        </div>
        @if ($f->status === 'ESCALATED')
            <form method="POST" action="{{ route('supervisor.escalations.close', $f->id) }}" class="panel-foot block">@csrf
                <div class="field mb-[.6rem]"><label>Decision or action taken</label><textarea name="response" rows="3" required></textarea></div>
                <div class="inline-form justify-end"><button class="btn" type="submit">Close with response</button></div>
            </form>
        @endif
    </div>
@empty
    <div class="panel"><div class="empty"><strong>No escalated concerns</strong>Concerns escalated by school governance bodies in your area appear here.</div></div>
@endforelse
@if(method_exists($items, 'links')){{ $items->links() }}@endif
@endsection
