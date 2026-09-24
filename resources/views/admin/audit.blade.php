@extends('layouts.app')
@section('title', 'Audit log')
@section('content')
<div class="page-head"><div><h1>Audit log</h1><div class="sub">Every sign in, change, approval and export across the system</div></div></div>
<div class="panel">
    <form class="panel-body filters" method="GET">
        <div class="field"><label>School</label><select name="school_id"><option value="">All schools</option>@foreach ($schools as $s)<option value="{{ $s->id }}" @selected(request('school_id') == $s->id)>{{ $s->name }}</option>@endforeach</select></div>
        <div class="field"><label>Action group</label>
            <select name="action"><option value="">All actions</option>
                @foreach (['auth' => 'Sign in and out', 'security' => 'Security alerts', 'account' => 'Account changes', 'school' => 'School records', 'user' => 'User invitations', 'marks' => 'Marks', 'results' => 'Results approval', 'maneb' => 'MANEB', 'inspection' => 'Inspections', 'feedback' => 'Concerns', 'learner' => 'Learner records'] as $k => $v)
                    <option value="{{ $k }}" @selected(request('action') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div class="actions"><button class="btn secondary" type="submit">Filter</button><a class="btn ghost" href="{{ route('admin.audit') }}">Clear</a></div>
    </form>
    @include('partials.audit-table', ['showSchool' => true])
</div>
@endsection
