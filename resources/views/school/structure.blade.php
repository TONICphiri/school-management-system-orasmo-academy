@extends('layouts.app')
@section('title', 'School structure')
@section('content')
@php $admin = auth()->user()->isSchoolAdmin(); $secondary = $school->type !== 'PRIMARY'; $primary = $school->type !== 'SECONDARY'; @endphp
<div class="page-head"><div><h1>School structure</h1><div class="sub">{{ $school->typeLabel() }} school on the {{ $school->structure }} structure</div></div></div>

<div class="panel">
    <div class="panel-head"><h2>Class levels</h2><span class="hint">Language of instruction follows the national policy: Chichewa for Standard 1 to 4, English from Standard 5</span></div>
    <form method="POST" action="{{ route('school.structure.levels') }}">@csrf
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Phase</th><th>Level</th><th>Language of instruction</th><th>National examination</th></tr></thead>
            <tbody>
            @foreach ($levels as $l)
                <tr>
                    <td><span class="badge {{ $l->phase === 'PRIMARY' ? 'info' : 'neutral' }}">{{ label($l->phase) }}</span></td>
                    <td>@if($admin)<input type="text" name="levels[{{ $l->id }}][name]" value="{{ $l->name }}">@else{{ $l->name }}@endif</td>
                    <td>@if($admin)<select name="levels[{{ $l->id }}][instruction_language]">@foreach (['Chichewa', 'English', 'Local language'] as $lang)<option @selected($l->instruction_language === $lang)>{{ $lang }}</option>@endforeach</select>@else{{ $l->instruction_language }}@endif</td>
                    <td>@if($admin)<select name="levels[{{ $l->id }}][national_exam]"><option value="">None</option>@foreach (['PSLCE', 'JCE', 'MSCE'] as $e)<option @selected($l->national_exam === $e)>{{ $e }}</option>@endforeach</select>@else{{ $l->national_exam ?: 'None' }}@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        @if ($admin)
        <div class="panel-foot">
            <div class="inline-form"><span class="small muted">Add level</span>
                <select name="new_phase">@if($primary)<option value="PRIMARY">Primary</option>@endif @if($secondary)<option value="SECONDARY">Secondary</option>@endif</select>
                <input type="text" name="new_name" placeholder="For example Form 5">
            </div>
            <button class="btn" type="submit">Save levels</button>
        </div>
        @endif
    </form>
</div>

<div class="grid grid-2">
    @if ($sections->count())
    <div class="panel">
        <div class="panel-head"><h2>Sections</h2><span class="hint">Infant, junior and senior sections each have a section head</span></div>
        <form method="POST" action="{{ route('school.structure.sections') }}">@csrf
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Section</th><th>Levels</th><th>Section head</th></tr></thead>
                <tbody>
                @foreach ($sections as $s)
                    <tr><td class="strong">{{ $s->name }}</td><td>Standard {{ $s->from_ordinal }} to {{ $s->to_ordinal }}</td>
                        <td>@if($admin)<select name="heads[{{ $s->id }}]"><option value="">Not assigned</option>@foreach ($staff as $u)<option value="{{ $u->id }}" @selected($s->head_id === $u->id)>{{ $u->name }}</option>@endforeach</select>@else{{ $s->head?->name ?? 'Not assigned' }}@endif</td></tr>
                @endforeach
                </tbody>
            </table></div>
            @if($admin)<div class="panel-foot"><span></span><button class="btn secondary" type="submit">Save section heads</button></div>@endif
        </form>
    </div>
    @endif

    @if ($secondary)
    <div class="panel">
        <div class="panel-head"><h2>Departments</h2><span class="hint">Heads of department validate subject marks</span></div>
        <form method="POST" action="{{ route('school.structure.departments.heads') }}">@csrf
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Department</th><th>Subjects</th><th>Head of department</th></tr></thead>
                <tbody>
                @forelse ($departments as $d)
                    <tr><td class="strong">{{ $d->name }}</td><td class="small">{{ $d->subjects->pluck('name')->implode(', ') ?: 'None' }}</td>
                        <td>@if($admin)<select name="heads[{{ $d->id }}]"><option value="">Not assigned</option>@foreach ($staff as $u)<option value="{{ $u->id }}" @selected($d->head_id === $u->id)>{{ $u->name }}{{ $u->role === 'HEAD_OF_DEPARTMENT' ? '' : ' ('.$u->roleLabel().')' }}</option>@endforeach</select>@else{{ $d->head?->name ?? 'Not assigned' }}@endif</td></tr>
                @empty
                    <tr><td colspan="3" class="empty">No departments yet.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            @if($admin)<div class="panel-foot"><span></span><button class="btn secondary" type="submit">Save heads</button></div>@endif
        </form>
        @if($admin)
        <form method="POST" action="{{ route('school.structure.departments') }}" class="panel-foot">@csrf
            <input type="text" name="name" placeholder="New department name" required><button class="btn ghost" type="submit">{{ icon('plus', 14) }} Add department</button>
        </form>
        @endif
    </div>
    @endif

    <div class="panel">
        <div class="panel-head"><h2>Committees</h2><span class="hint">Discipline, sports, health and other school committees</span></div>
        <ul class="list">
            @forelse ($committees as $c)
                <li><span><strong>{{ $c->name }}</strong><div class="small muted">{{ $c->members->map(fn ($m) => $m->name.($m->pivot->position ? ' ('.$m->pivot->position.')' : ''))->implode(', ') ?: 'No members yet' }}</div></span><span class="badge neutral">{{ $c->members->count() }}</span></li>
            @empty
                <li class="muted">No committees yet.</li>
            @endforelse
        </ul>
        @if($admin)
        <form method="POST" action="{{ route('school.structure.committees') }}" class="panel-foot">@csrf
            <input type="text" name="name" placeholder="New committee name" required><button class="btn ghost" type="submit">{{ icon('plus', 14) }} Add committee</button>
        </form>
        @endif
    </div>
</div>
@endsection
