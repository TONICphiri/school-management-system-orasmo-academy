@extends('layouts.app')
@section('title', 'Staff')
@section('content')
@php $admin = auth()->user()->isSchoolAdmin(); @endphp
<div class="page-head">
    <div><h1>Staff</h1><div class="sub">{{ $staff->count() }} teaching staff. Primary class teachers need at least a T2 certificate, secondary teachers a Diploma or Bachelor.</div></div>
    @if($admin)<div class="actions"><a class="btn" href="{{ route('school.staff.create') }}">{{ icon('plus', 16) }} Add staff member</a></div>@endif
</div>
<div class="panel">
    <form class="panel-body filters" method="GET">
        <div class="field"><label>Search</label><input type="search" name="q" value="{{ request('q') }}" placeholder="Name"></div>
        <div class="field"><label>Role</label><select name="role"><option value="">All roles</option>@foreach ($roles as $k => $v)<option value="{{ $k }}" @selected(request('role') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="actions"><button class="btn secondary" type="submit">Filter</button></div>
    </form>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Name</th><th>Role</th><th>Qualification</th><th>Class</th><th class="num">Subjects</th><th class="num">Periods a week</th><th>Status</th>@if($admin)<th></th>@endif</tr></thead>
        <tbody>
        @forelse ($staff as $u)
            @php $load = $loads[$u->id] ?? null; @endphp
            <tr>
                <td><span class="strong">{{ $u->name }}</span><div class="small muted">{{ $u->teacherProfile?->employment_number ?: 'No TSC number' }}</div></td>
                <td>{{ $u->roleLabel() }}@if($u->committees->count())<div class="small muted">{{ $u->committees->pluck('name')->implode(', ') }}</div>@endif</td>
                <td>{{ \App\Models\TeacherProfile::QUALIFICATIONS[$u->teacherProfile?->qualification] ?? 'Not recorded' }}</td>
                <td>{{ isset($classOf[$u->id]) ? $classOf[$u->id]->name() : '' }}</td>
                <td class="num">{{ $load?->lessons ?? 0 }}</td>
                <td class="num">{{ $load?->periods ?? 0 }}</td>
                <td><span class="badge {{ status_tone($u->status) }}">{{ label($u->status) }}</span></td>
                @if($admin)
                <td class="right nowrap">
                    @if($u->role !== 'FACILITY_ADMIN' || $u->id === auth()->id())<a class="btn ghost small" href="{{ route('school.staff.edit', $u) }}">{{ icon('edit', 14) }} Edit</a>@endif
                    @if($u->status === 'PENDING_ACTIVATION')
                        <form method="POST" action="{{ route('school.staff.resend', $u) }}" style="display:inline">@csrf<button class="btn ghost small" type="submit">Resend code</button></form>
                    @endif
                    @if($u->id !== auth()->id() && $u->role !== 'FACILITY_ADMIN')
                        <form method="POST" action="{{ route('school.staff.status', $u) }}" style="display:inline" data-confirm="{{ $u->status === 'SUSPENDED' ? 'Restore' : 'Suspend' }} the account of {{ $u->name }}?">@csrf<button class="btn ghost small" type="submit">{{ $u->status === 'SUSPENDED' ? 'Restore' : 'Suspend' }}</button></form>
                    @endif
                </td>
                @endif
            </tr>
        @empty
            <tr><td colspan="8" class="empty">No staff match.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
@endsection
