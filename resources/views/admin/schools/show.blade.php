@extends('layouts.app')
@section('title', $school->name)
@section('crumbs')<a href="{{ route('admin.schools.index') }}">Schools</a> / @endsection
@section('content')
<div class="page-head">
    <div><h1>{{ $school->name }} <span class="badge {{ status_tone($school->status) }} align-middle">{{ $school->statusLabel() }}</span></h1>
    <div class="sub">{{ $school->code }} &middot; {{ $school->typeLabel() }} &middot; {{ $school->categoryLabel() }} &middot; {{ $school->structure }}</div></div>
    <div class="actions">
        <a class="btn secondary" href="{{ route('admin.schools.edit', $school) }}">{{ icon('edit', 16) }} Edit details</a>
        @if ($sysadmin && $sysadmin->status === 'PENDING_ACTIVATION')
            <form method="POST" action="{{ route('admin.schools.resend', $school) }}">@csrf<button class="btn" type="submit">{{ icon('send', 16) }} Resend activation</button></form>
        @endif
    </div>
</div>
<div class="stats">
    <div class="stat"><div class="label">Learners</div><div class="value">{{ $stats['enrolment'] }}</div><div class="foot">{{ $stats['girls'] }} girls, {{ $stats['boys'] }} boys</div></div>
    <div class="stat"><div class="label">Teachers</div><div class="value">{{ $stats['teachers'] }}</div><div class="foot">{{ $stats['qualified'] }} qualified</div></div>
    <div class="stat"><div class="label">Attendance</div><div class="value">{{ num($stats['attendance']) }}<small>%</small></div></div>
    <div class="stat blue"><div class="label">Marks submitted</div><div class="value">{{ num($stats['completion']) }}<small>%</small></div></div>
</div>
<div class="grid grid-main">
    <div class="stack">
        <div class="panel">
            <div class="panel-head"><h2>Details</h2></div>
            <div class="panel-body">
                <dl class="kv">
                    <dt>Division</dt><dd>{{ $school->division->name }}</dd>
                    <dt>District</dt><dd>{{ $school->district->name }}</dd>
                    <dt>Zone</dt><dd>{{ $school->zone?->name ?? 'Not set' }}</dd>
                    <dt>Reports go to</dt><dd>{{ $school->directorate() }}</dd>
                    <dt>MANEB centre</dt><dd>{{ $school->maneb_centre_number ?: 'Not set' }}</dd>
                    <dt>Address</dt><dd>{{ $school->postal_address ?: 'Not set' }}</dd>
                    <dt>Contact</dt><dd>{{ collect([$school->phone, $school->email])->filter()->implode(', ') ?: 'Not set' }}</dd>
                    <dt>Head teacher</dt><dd>@if($head){{ $head->name }} <span class="badge {{ status_tone($head->status) }}">{{ label($head->status) }}</span>@else Not yet registered @endif</dd>
                    @if($sysadmin && $sysadmin->role === 'SCHOOL_ADMIN')<dt>System administrator</dt><dd>{{ $sysadmin->name }} <span class="badge {{ status_tone($sysadmin->status) }}">{{ label($sysadmin->status) }}</span></dd>@endif
                </dl>
            </div>
        </div>
        <div class="panel">
            <div class="panel-head"><h2>Academic calendar</h2></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Year</th><th>Term</th><th>Opens</th><th>Closes</th><th></th></tr></thead>
                <tbody>
                @foreach ($years as $y)
                    @foreach ($y->terms->sortBy('number') as $t)
                        <tr><td>{{ $y->name }}</td><td>Term {{ $t->number }}</td><td>{{ $t->starts_on->format('j M Y') }}</td><td>{{ $t->ends_on->format('j M Y') }}</td><td>@if($t->is_current)<span class="badge ok">Current</span>@endif</td></tr>
                    @endforeach
                @endforeach
                </tbody>
            </table></div>
        </div>
        <div class="panel">
            <div class="panel-head"><h2>Staff and governance accounts</h2><span class="hint">{{ $staff->count() }}</span></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Last sign in</th></tr></thead>
                <tbody>
                @foreach ($staff as $u)
                    <tr><td class="strong">{{ $u->name }}</td><td>{{ $u->roleLabel() }}</td><td><span class="badge {{ status_tone($u->status) }}">{{ label($u->status) }}</span></td><td>{{ $u->last_login_at?->format('j M Y') ?? 'Never' }}</td></tr>
                @endforeach
                </tbody>
            </table></div>
        </div>
    </div>
    <div class="stack">
        <div class="panel">
            <div class="panel-head"><h2>Change status</h2></div>
            <form method="POST" action="{{ route('admin.schools.status', $school) }}" data-confirm="Change the status of this school? The head teacher will be notified.">
                @csrf
                <div class="panel-body">
                    <div class="field mb-[.8rem]"><label>Status</label>
                        <select name="status">@foreach (\App\Models\School::STATUSES as $k => $v)<option value="{{ $k }}" @selected($school->status === $k)>{{ $v }}</option>@endforeach</select>
                    </div>
                    <div class="field"><label>Reason</label><input type="text" name="reason" placeholder="Recorded in the audit log"></div>
                </div>
                <div class="panel-foot"><span class="small muted">Suspended schools cannot sign in.</span><button class="btn danger" type="submit">Update status</button></div>
            </form>
        </div>
        <div class="panel">
            <div class="panel-head"><h2>Audit trail</h2></div>
            <ul class="timeline">
                @forelse ($logs as $log)
                    <li><time>{{ $log->created_at->format('j M, H:i') }}</time><span>{{ $log->description }}</span></li>
                @empty
                    <li class="muted">No activity recorded.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
