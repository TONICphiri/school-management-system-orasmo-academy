@extends('layouts.app')
@section('title', 'Academic calendar')
@section('content')
@php $admin = auth()->user()->isSchoolAdmin(); @endphp
<div class="page-head"><div><h1>Academic calendar</h1><div class="sub">Three terms per year with mid-term breaks. Changes are sent to staff and parents.</div></div></div>
<div class="grid grid-main">
<div class="stack">
@forelse ($years as $year)
    <div class="panel">
        <div class="panel-head"><h2>{{ $year->name }} @if($year->is_current)<span class="badge ok">Current year</span>@endif</h2><span class="hint">{{ $year->starts_on?->format('j M Y') }} to {{ $year->ends_on?->format('j M Y') }}</span></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Term</th><th>Opens</th><th>Closes</th><th>Weeks</th><th>Breaks</th><th></th></tr></thead>
            <tbody>
            @foreach ($year->terms as $t)
                <tr>
                    <td class="strong">Term {{ $t->number }} @if($t->is_current)<span class="badge ok">Current</span>@endif</td>
                    @if ($admin)
                        <td colspan="2">
                            <form method="POST" action="{{ route('school.calendar.terms') }}" class="inline-form">@csrf
                                <input type="hidden" name="term_id" value="{{ $t->id }}">
                                <input type="date" name="starts_on" value="{{ $t->starts_on->toDateString() }}">
                                <input type="date" name="ends_on" value="{{ $t->ends_on->toDateString() }}">
                                <button class="btn secondary small" type="submit">Save</button>
                            </form>
                        </td>
                    @else
                        <td>{{ $t->starts_on->format('D j M Y') }}</td><td>{{ $t->ends_on->format('D j M Y') }}</td>
                    @endif
                    <td class="num">{{ (int) round($t->starts_on->diffInWeeks($t->ends_on)) }}</td>
                    <td>@forelse ($t->breaks as $b)<div class="small"><strong>{{ $b->name }}</strong> {{ $b->starts_on->format('j M') }} to {{ $b->ends_on->format('j M') }}</div>@empty<span class="small muted">None</span>@endforelse</td>
                    <td class="right">@if($admin && ! $t->is_current)<form method="POST" action="{{ route('school.calendar.current', $t) }}" data-confirm="Make Term {{ $t->number }} the current term? New marks and attendance will record against it.">@csrf<button class="btn ghost small" type="submit">Make current</button></form>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    </div>
@empty
    <div class="panel"><div class="empty"><strong>No academic year</strong>Add the first academic year to begin.</div></div>
@endforelse
</div>
@if ($admin)
<div class="stack">
    <div class="panel">
        <div class="panel-head"><h2>Add a break</h2></div>
        <form method="POST" action="{{ route('school.calendar.breaks') }}">@csrf
            <div class="panel-body stack-sm">
                <div class="field"><label>Term</label><select name="term_id">@foreach ($years as $y)@foreach ($y->terms as $t)<option value="{{ $t->id }}" @selected($t->is_current)>{{ $y->name }}, Term {{ $t->number }}</option>@endforeach @endforeach</select></div>
                <div class="field"><label>Name</label><input type="text" name="name" placeholder="Mid-term break" required></div>
                <div class="field"><label>From</label><input type="date" name="starts_on" required></div>
                <div class="field"><label>To</label><input type="date" name="ends_on" required></div>
            </div>
            <div class="panel-foot"><span class="small muted">Staff and parents are notified.</span><button class="btn" type="submit">Add break</button></div>
        </form>
    </div>
    <div class="panel">
        <div class="panel-head"><h2>Add academic year</h2></div>
        <form method="POST" action="{{ route('school.calendar.years') }}">@csrf
            <div class="panel-body stack-sm">
                <div class="field"><label>Name</label><input type="text" name="name" placeholder="2027/2028" required></div>
                <div class="field"><label>Starts</label><input type="date" name="starts_on" required></div>
                <div class="field"><label>Ends</label><input type="date" name="ends_on" required></div>
            </div>
            <div class="panel-foot"><span></span><button class="btn secondary" type="submit">Add year</button></div>
        </form>
    </div>
</div>
@endif
</div>
@endsection
