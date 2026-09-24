@extends('layouts.app')
@section('title', 'Learners')
@section('content')
<div class="page-head">
    <div><h1>Learners</h1><div class="sub">{{ $students->total() }} records. Learner details are visible only to school leaders and the learner's own class teacher.</div></div>
    @if($canManage)<div class="actions"><a class="btn" href="{{ route('school.students.create') }}">{{ icon('plus', 16) }} Enrol a learner</a></div>@endif
</div>
<div class="panel">
    <form class="panel-body filters" method="GET">
        <div class="field"><label>Search</label><input type="search" name="q" value="{{ request('q') }}" placeholder="Name or admission number"></div>
        <div class="field"><label>Class</label><select name="class_id"><option value="">All classes</option>@foreach ($classes as $c)<option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>{{ $c->name() }}</option>@endforeach</select></div>
        <div class="field"><label>Gender</label><select name="gender"><option value="">All</option><option @selected(request('gender') === 'Female')>Female</option><option @selected(request('gender') === 'Male')>Male</option></select></div>
        <div class="actions"><button class="btn secondary" type="submit">Filter</button><a class="btn ghost" href="{{ route('school.students.index') }}">Clear</a></div>
    </form>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Learner</th><th>Admission</th><th>Class</th><th>Gender</th><th>Age</th><th>Home district</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($students as $s)
            <tr>
                <td><a class="strong" href="{{ route('school.students.show', $s) }}">{{ $s->fullName() }}</a></td>
                <td>{{ $s->admission_number }}</td>
                <td>{{ $s->schoolClass?->name() }}</td>
                <td>{{ $s->gender }}</td>
                <td class="num">{{ $s->date_of_birth?->age }}</td>
                <td>{{ $s->home_district }}</td>
                <td><span class="badge {{ status_tone($s->status) }}">{{ label($s->status) }}</span></td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">No learners match.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $students->links() }}
</div>
@endsection
