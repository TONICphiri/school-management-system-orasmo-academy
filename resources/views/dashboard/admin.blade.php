@extends('layouts.app')
@section('title', 'National overview')
@section('content')
<div class="page-head">
    <div><h1>National overview</h1><div class="sub">Schools, users and security across all education divisions</div></div>
    <div class="actions"><a class="btn" href="{{ route('admin.schools.create') }}">{{ icon('plus', 16) }} Register a school</a></div>
</div>

<div class="stats">
    <div class="stat"><div class="label">Schools</div><div class="value">{{ $schoolCount }}</div><div class="foot">{{ $byType['PRIMARY'] ?? 0 }} primary, {{ $byType['SECONDARY'] ?? 0 }} secondary, {{ $byType['COMBINED'] ?? 0 }} combined</div></div>
    <div class="stat"><div class="label">Active schools</div><div class="value">{{ $byStatus['ACTIVE'] ?? 0 }}</div><div class="foot">{{ $byStatus['SUSPENDED'] ?? 0 }} suspended</div></div>
    <div class="stat amber"><div class="label">Awaiting activation</div><div class="value">{{ $byStatus['PENDING_ACTIVATION'] ?? 0 }}</div><div class="foot">{{ $pendingUsers }} user accounts pending</div></div>
    <div class="stat blue"><div class="label">Learners enrolled</div><div class="value">{{ number_format($learners) }}</div><div class="foot">{{ number_format($userCount) }} active user accounts</div></div>
    <div class="stat"><div class="label">Supervisors</div><div class="value">{{ $supervisors }}</div><div class="foot">EDM, DEM and PEA accounts</div></div>
</div>

<div class="grid grid-main">
    <div class="stack">
        <div class="panel">
            <div class="panel-head"><h2>Schools awaiting activation</h2><a href="{{ route('admin.schools.index', ['status' => 'PENDING_ACTIVATION']) }}" class="small">View all</a></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>School</th><th>District</th><th>Registered</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($pendingSchools as $s)
                        <tr>
                            <td><a class="strong" href="{{ route('admin.schools.show', $s) }}">{{ $s->name }}</a><div class="small muted">{{ $s->code }} &middot; {{ $s->typeLabel() }}</div></td>
                            <td>{{ $s->district->name }}</td>
                            <td class="nowrap">{{ $s->created_at->format('j M Y') }}</td>
                            <td class="right">
                                <form method="POST" action="{{ route('admin.schools.resend', $s) }}" class="inline">@csrf<button class="btn secondary small" type="submit">Resend code</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">Every registered school has been activated.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="panel">
            <div class="panel-head"><h2>Inspection reports flagged for follow-up</h2><a href="{{ route('admin.inspections') }}" class="small">All reports</a></div>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>School</th><th>Directorate</th><th>Visited by</th><th>Follow up by</th></tr></thead>
                    <tbody>
                    @forelse ($flagged as $r)
                        <tr>
                            <td class="strong">{{ $r->school->name }}</td>
                            <td>{{ $r->directorate }}</td>
                            <td>{{ $r->supervisor->name }}<div class="small muted">{{ $r->supervisor->roleLabel() }}</div></td>
                            <td class="nowrap"><span class="badge {{ $r->follow_up_by && $r->follow_up_by->isPast() ? 'bad' : 'warn' }}">{{ $r->follow_up_by?->format('j M Y') }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">No reports are waiting for follow-up.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="stack">
        <div class="panel">
            <div class="panel-head"><h2>Security alerts</h2><a href="{{ route('admin.audit', ['action' => 'security']) }}" class="small">Audit log</a></div>
            @if ($security->isEmpty())
                <div class="empty">No security events recorded.</div>
            @else
                <ul class="timeline">
                    @foreach ($security as $log)
                        <li><time>{{ $log->created_at->format('j M, H:i') }}</time><span><span class="badge bad">{{ label(str_replace('security.', '', $log->action)) }}</span> {{ $log->description }}</span></li>
                    @endforeach
                </ul>
            @endif
        </div>
        <div class="panel">
            <div class="panel-head"><h2>Recent activity</h2></div>
            <ul class="timeline">
                @forelse ($recent as $log)
                    <li><time>{{ $log->created_at->format('j M, H:i') }}</time><span>{{ $log->description }}@if($log->school)<div class="small muted">{{ $log->school->name }}</div>@endif</span></li>
                @empty
                    <li><span class="muted">No activity yet.</span></li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
