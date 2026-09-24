@extends('layouts.app')
@section('title', 'Supervision overview')
@section('content')
@php $me = auth()->user(); @endphp
<div class="page-head">
    <div><h1>{{ $me->scopeLabel() }}</h1><div class="sub">{{ $me->roleLabel() }} &middot; read only view of {{ $rows->count() }} {{ $me->role === 'EDM' ? 'secondary' : 'primary' }} {{ Str::plural('school', $rows->count()) }}</div></div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('supervisor.escalations') }}">{{ icon('flag', 16) }} Escalated concerns @if($escalations)<span class="badge bad">{{ $escalations }}</span>@endif</a>
        <a class="btn secondary" href="{{ route('supervisor.inspections.index') }}">{{ icon('clipboard', 16) }} Inspection reports</a>
    </div>
</div>

<div class="stats">
    <div class="stat"><div class="label">Learners enrolled</div><div class="value">{{ number_format($totals['enrolment']) }}</div></div>
    <div class="stat"><div class="label">Teachers</div><div class="value">{{ $totals['teachers'] }}</div><div class="foot">Learner to teacher ratio {{ $totals['teachers'] ? num($totals['enrolment'] / $totals['teachers']) : 'n/a' }}</div></div>
    <div class="stat {{ pct_tone($totals['attendance'], 90, 80) }}"><div class="label">Attendance this term</div><div class="value">{{ num($totals['attendance']) }}<small>%</small></div></div>
    <div class="stat blue"><div class="label">Marks submitted</div><div class="value">{{ num($totals['completion']) }}<small>%</small></div><div class="foot">Of class subjects this term</div></div>
    <div class="stat {{ pct_tone($totals['pass_rate'], 70, 50) }}"><div class="label">Pass rate</div><div class="value">{{ num($totals['pass_rate']) }}<small>%</small></div><div class="foot">Scores of 40 percent and above</div></div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Schools</h2><span class="hint">Select a school to see classes, teachers and inspection history</span></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>School</th><th>Zone</th><th>Status</th><th class="num">Learners</th><th class="num">Girls</th><th class="num">Teachers</th><th class="num">Qualified</th><th class="num">Attendance</th><th>Marks submitted</th><th class="num">Pass rate</th><th></th></tr></thead>
            <tbody>
            @forelse ($rows as $r)
                @php $s = $r['school']; $st = $r['stats']; @endphp
                <tr>
                    <td><a class="strong" href="{{ route('supervisor.school', $s) }}">{{ $s->name }}</a><div class="small muted">{{ $s->code }} &middot; {{ $s->categoryLabel() }}</div></td>
                    <td>{{ $s->zone?->name ?? $s->district->name }}</td>
                    <td><span class="badge {{ status_tone($s->status) }}">{{ $s->statusLabel() }}</span></td>
                    <td class="num">{{ $st['enrolment'] }}</td>
                    <td class="num">{{ $st['girls'] }}</td>
                    <td class="num">{{ $st['teachers'] }}</td>
                    <td class="num">{{ $st['qualified'] }}</td>
                    <td class="num">{{ num($st['attendance']) }}{{ $st['attendance'] !== null ? '%' : '' }}</td>
                    <td style="min-width:130px"><div class="progress"><span style="width:{{ (int) $st['completion'] }}%"></span></div><span class="small muted">{{ num($st['completion']) }}{{ $st['completion'] !== null ? '%' : '' }}</span></td>
                    <td class="num">{{ num($st['pass_rate']) }}{{ $st['pass_rate'] !== null ? '%' : '' }}</td>
                    <td class="right"><a class="btn secondary small" href="{{ route('supervisor.inspections.create', $s) }}">New inspection</a></td>
                </tr>
            @empty
                <tr><td colspan="11" class="empty"><strong>No schools in your area yet</strong>Schools appear here once the Ministry registers them in your {{ $me->role === 'EDM' ? 'division' : ($me->role === 'DEM' ? 'district' : 'zone') }}.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Flagged for follow-up</h2></div>
    <ul class="list">
        @forelse ($flagged as $r)
            <li><span><a href="{{ route('supervisor.inspections.show', $r) }}" class="strong">{{ $r->school->name }}</a> <span class="muted small">visited {{ $r->visit_date->format('j M Y') }}</span></span><span class="badge {{ $r->follow_up_by?->isPast() ? 'bad' : 'warn' }}">Follow up by {{ $r->follow_up_by?->format('j M Y') }}</span></li>
        @empty
            <li class="muted">No inspection reports are flagged.</li>
        @endforelse
    </ul>
</div>
@endsection
