@extends('layouts.app')
@section('title', 'Inspection report')
@section('crumbs')<a href="{{ route('supervisor.inspections.index') }}">Inspections</a> / @endsection
@section('content')
<div class="page-head">
    <div><h1>{{ $report->school->name }}</h1><div class="sub">Visited {{ $report->visit_date->format('l j F Y') }} by {{ $report->supervisor->name }}, {{ $report->supervisor->roleLabel() }}</div></div>
    <div class="actions"><button class="btn secondary" type="button" onclick="window.print()">{{ icon('printer', 16) }} Print</button></div>
</div>
<div class="grid grid-main">
    <div class="stack">
        <div class="panel"><div class="panel-head"><h2>Findings</h2></div><div class="panel-body whitespace-pre-line">{{ $report->findings }}</div></div>
        <div class="panel"><div class="panel-head"><h2>Recommendations</h2></div><div class="panel-body whitespace-pre-line">{{ $report->recommendations }}</div></div>
    </div>
    <div class="panel">
        <div class="panel-head"><h2>Summary</h2></div>
        <div class="panel-body"><dl class="kv">
            <dt>Rating</dt><dd><span class="badge {{ status_tone($report->overall_rating) }}">{{ $ratings[$report->overall_rating] }}</span></dd>
            <dt>Routed to</dt><dd>{{ $report->directorate }}</dd>
            <dt>District</dt><dd>{{ $report->school->district->name }}</dd>
            <dt>Status</dt><dd>{{ label($report->status) }}</dd>
            <dt>Follow up</dt><dd>{{ $report->flag_follow_up ? 'By '.$report->follow_up_by?->format('j M Y') : 'Not required' }}</dd>
            <dt>Submitted</dt><dd>{{ $report->created_at->format('j M Y, H:i') }}</dd>
        </dl></div>
    </div>
</div>
@endsection
