@extends('layouts.app')
@section('title', $school->name)
@section('crumbs')<a href="{{ route('dashboard') }}">My area</a> / @endsection
@section('content')
@php $stages = \App\Http\Controllers\School\ResultController::STAGES; @endphp
<div class="page-head">
    <div><h1>{{ $school->name }} <span class="badge {{ status_tone($school->status) }}" style="vertical-align:middle">{{ $school->statusLabel() }}</span></h1>
    <div class="sub">{{ $school->code }} &middot; {{ $school->typeLabel() }}, {{ $school->categoryLabel() }} &middot; {{ $school->zone?->name ? $school->zone->name.' zone, ' : '' }}{{ $school->district->name }}, {{ $school->division->name }} &middot; read only</div></div>
    <div class="actions"><a class="btn" href="{{ route('supervisor.inspections.create', $school) }}">{{ icon('clipboard', 16) }} Record inspection visit</a></div>
</div>
@include('supervisor._stats')
@include('supervisor._exams')
<div class="panel">
    <div class="panel-head"><h2>Classes</h2><span class="hint">{{ $stats['term']?->label() }}</span></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Class</th><th>Class teacher</th><th class="num">Learners</th><th class="num">Girls</th><th>Subject marks submitted</th><th>Results stage</th></tr></thead>
        <tbody>
        @forelse ($classRows as $r)
            @php $pct = $r['lessons'] ? $r['done'] / $r['lessons'] * 100 : 0; @endphp
            <tr>
                <td class="strong">{{ $r['class']->name() }}</td>
                <td>{{ $r['class']->classTeacher?->name ?? '' }}@unless($r['class']->classTeacher)<span class="badge warn">Vacant</span>@endunless</td>
                <td class="num">{{ $r['learners'] }}</td>
                <td class="num">{{ $r['girls'] }}</td>
                <td style="min-width:150px"><div class="progress {{ $pct < 50 ? 'red' : ($pct < 100 ? 'amber' : '') }}"><span style="width:{{ $pct }}%"></span></div><div class="small muted">{{ $r['done'] }} of {{ $r['lessons'] }}</div></td>
                <td><span class="badge {{ status_tone($r['stage']) }}">{{ $stages[$r['stage']] ?? label($r['stage']) }}</span></td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">No classes set up for the current year.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
<div class="grid grid-2">
    <div class="panel">
        <div class="panel-head"><h2>Teaching staff</h2><span class="hint">{{ $teachers->count() }}</span></div>
        <div class="table-wrap"><table class="table compact">
            <thead><tr><th>Name</th><th>Role</th><th>Qualification</th><th>Gender</th></tr></thead>
            <tbody>
            @foreach ($teachers as $t)
                <tr><td>{{ $t->name }}</td><td class="small">{{ $t->roleLabel() }}</td><td class="small">{{ $qualifications[$t->teacherProfile?->qualification] ?? 'Not recorded' }}</td><td>{{ $t->gender }}</td></tr>
            @endforeach
            </tbody>
        </table></div>
    </div>
    <div class="panel">
        <div class="panel-head"><h2>Inspection history</h2></div>
        <ul class="list">
            @forelse ($inspections as $r)
                <li><span><a href="{{ route('supervisor.inspections.show', $r) }}">{{ $r->visit_date->format('j M Y') }}</a><div class="small muted">{{ $r->supervisor->name }}, {{ $r->supervisor->role }}</div></span>
                    <span>@if($r->flag_follow_up)<span class="badge warn">Follow up by {{ $r->follow_up_by?->format('j M') }}</span>@endif <span class="badge {{ status_tone($r->overall_rating) }}">{{ label($r->overall_rating) }}</span></span></li>
            @empty
                <li class="muted">No visits recorded.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
