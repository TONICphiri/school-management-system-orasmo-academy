@extends('layouts.app')
@section('title', 'Results approval')
@section('content')
<div class="page-head"><div><h1>Results approval</h1><div class="sub">{{ $term?->label() ?? 'No current term' }}. Primary: subject teacher, class teacher, head teacher. Secondary: subject teacher, head of department, form master, Deputy Head Academic, head teacher.</div></div></div>
@php $counts = collect($rows)->countBy('stage'); @endphp
<div class="pipeline mb-[1.25rem]" style="grid-template-columns:repeat({{ count($stages) }}, 1fr)">
    @foreach ($stages as $k => $v)
        <div class="step {{ ($counts[$k] ?? 0) ? 'current' : '' }}"><div class="n">{{ $counts[$k] ?? 0 }}</div><div class="t">{{ $v }}</div></div>
    @endforeach
</div>
<div class="panel">
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Class</th><th>Class leader</th><th>Subjects submitted</th><th>Validated</th><th>Stage</th><th></th></tr></thead>
        <tbody>
        @forelse ($rows as $r)
            @php $c = $r['class']; $u = auth()->user(); $ctl = app(\App\Http\Controllers\School\ResultController::class);
                $canOpen = $u->isSchoolLeader() || $c->class_teacher_id === $u->id || $c->classSubjects->contains(fn ($cs) => $cs->teacher_id === $u->id || $ctl->canValidate($u, $cs));
                $pct = $r['total'] ? $r['submitted'] / $r['total'] * 100 : 0; @endphp
            <tr>
                <td class="strong">{{ $c->name() }}</td>
                <td>{{ $c->classTeacher?->name ?? 'Not assigned' }}</td>
                <td class="min-w-[140px]"><div class="progress {{ $pct < 100 ? 'amber' : '' }}"><span style="width:{{ $pct }}%"></span></div><div class="small muted">{{ $r['submitted'] }} of {{ $r['total'] }}</div></td>
                <td class="num">{{ $c->isSecondary() ? $r['validated'].' of '.$r['total'] : 'Not required' }}</td>
                <td><span class="badge {{ status_tone($r['stage']) }}">{{ $stages[$r['stage']] }}</span></td>
                <td class="right">@if($canOpen)<a class="btn secondary small" href="{{ route('school.results.show', $c) }}">Open</a>@endif</td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">No classes this term.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
@endsection
