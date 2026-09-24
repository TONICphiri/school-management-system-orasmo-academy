@extends('layouts.app')
@section('title', 'Register for '.$class->name())
@section('crumbs')<a href="{{ route('school.classes.show', $class) }}">{{ $class->name() }}</a> / @endsection
@section('content')
<div class="page-head">
    <div><h1>Daily register</h1><div class="sub">{{ $class->name() }} &middot; {{ \Carbon\Carbon::parse($date)->format('l j F Y') }} &middot; {{ $students->count() }} learners</div></div>
    <form method="GET" class="inline-form"><input type="date" name="date" value="{{ $date }}" max="{{ now()->toDateString() }}"><button class="btn secondary" type="submit">Open date</button></form>
</div>
@if ($existing->count())<div class="alert info">The register for this day was already taken. Saving will update it.</div>@endif
<form method="POST" action="{{ route('school.attendance.store', $class) }}">@csrf
    <input type="hidden" name="date" value="{{ $date }}">
    <div class="panel">
        <div class="table-wrap"><table class="table">
            <thead><tr><th>#</th><th>Learner</th><th>Present</th><th>Late</th><th>Absent</th><th>Excused</th></tr></thead>
            <tbody>
            @foreach ($students as $i => $s)
                @php $cur = $existing[$s->id] ?? 'PRESENT'; @endphp
                <tr>
                    <td class="muted">{{ $i + 1 }}</td>
                    <td class="strong">{{ $s->fullName() }}</td>
                    @foreach (['PRESENT', 'LATE', 'ABSENT', 'EXCUSED'] as $st)
                        <td><label class="check"><input type="radio" name="status[{{ $s->id }}]" value="{{ $st }}" @checked($cur === $st)></label></td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table></div>
        <div class="panel-foot"><span class="small muted">Parents of absent learners receive an SMS.</span><button class="btn" type="submit">Save register</button></div>
    </div>
</form>
@endsection
