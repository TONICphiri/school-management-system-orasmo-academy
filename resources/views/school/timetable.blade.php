@extends('layouts.app')
@section('title', 'Timetable')
@section('content')
@php $times = \App\Services\Timetable::PERIOD_TIMES; $days = \App\Models\TimetableSlot::DAYS; @endphp
<div class="page-head">
    <div><h1>Timetable</h1><div class="sub">{{ $term?->label() ?? 'No current term' }} &middot; {{ $slotCount }} lessons placed</div></div>
    @if($canManage && $term)
        <div class="actions"><form method="POST" action="{{ route('school.timetable.generate') }}" data-confirm="Rebuild the master timetable for {{ $term->label() }}? The current timetable will be replaced and staff notified.">@csrf<button class="btn" type="submit">{{ icon('grid', 16) }} Generate timetable</button></form></div>
    @endif
</div>
@if (count($conflicts))
    <div class="alert error"><strong>{{ count($conflicts) }} clashes found</strong><ul style="margin:.4rem 0 0 1rem">@foreach (array_slice($conflicts, 0, 8) as $c)<li>{{ $c }}</li>@endforeach</ul></div>
@elseif ($slotCount)
    <div class="alert success">No clashes. Every teacher and class has at most one lesson per period.</div>
@endif
@if (session('unplaced'))
    <div class="alert warn"><strong>Lessons that could not be placed</strong><ul style="margin:.4rem 0 0 1rem">@foreach (session('unplaced') as $u)<li>{{ $u }}</li>@endforeach</ul></div>
@endif
<div class="tabs">
    <a href="{{ route('school.timetable.index', ['view' => 'teacher']) }}" class="{{ $view === 'teacher' ? 'active' : '' }}">By teacher</a>
    <a href="{{ route('school.timetable.index', ['view' => 'class']) }}" class="{{ $view === 'class' ? 'active' : '' }}">By class</a>
</div>
<div class="panel">
    <form class="panel-body filters" method="GET">
        <input type="hidden" name="view" value="{{ $view }}">
        @if ($view === 'teacher')
            <div class="field"><label>Teacher</label><select name="teacher_id" onchange="this.form.submit()">@foreach ($teachers as $t)<option value="{{ $t->id }}" @selected($selectedTeacher?->id === $t->id)>{{ $t->name }}</option>@endforeach</select></div>
        @else
            <div class="field"><label>Class</label><select name="class_id" onchange="this.form.submit()">@foreach ($classes as $c)<option value="{{ $c->id }}" @selected($selectedClass?->id === $c->id)>{{ $c->name() }}</option>@endforeach</select></div>
        @endif
        <div class="legend"><span><span class="grade g-top">&nbsp;</span> Lesson</span><span><span class="grade g-fail">&nbsp;</span> Clash</span></div>
    </form>
    <div class="table-wrap" style="padding:0 1rem 1rem">
        <table class="tt">
            <thead><tr><th style="width:90px">Period</th>@foreach ($days as $d)<th>{{ $d }}</th>@endforeach</tr></thead>
            <tbody>
            @foreach ($times as $p => $start)
                @if ($p === 5)<tr><td colspan="6" class="small muted" style="text-align:center;background:#f7f8f5">Break 10:10 to 10:30</td></tr>@endif
                @if ($p === 8)<tr><td colspan="6" class="small muted" style="text-align:center;background:#f7f8f5">Lunch 12:30 to 13:30</td></tr>@endif
                <tr>
                    <th><div>Period {{ $p }}</div><div class="small muted">{{ $start }}</div></th>
                    @foreach ($days as $d => $dn)
                        @php $cell = $grid[$d][$p] ?? []; @endphp
                        <td class="slot">
                            @foreach ($cell as $slot)
                                <div class="lesson {{ count($cell) > 1 ? 'clash' : '' }}">
                                    <strong>{{ $slot->classSubject->subject->name }}</strong>
                                    <span>{{ $view === 'teacher' ? $slot->classSubject->schoolClass->name() : ($slot->classSubject->teacher?->name ?? 'No teacher') }}</span>
                                </div>
                            @endforeach
                        </td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
