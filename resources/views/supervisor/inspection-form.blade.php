@extends('layouts.app')
@section('title', 'Inspection visit')
@section('crumbs')<a href="{{ route('supervisor.school', $school) }}">{{ $school->name }}</a> / @endsection
@section('content')
<div class="page-head"><div><h1>Inspection visit</h1><div class="sub">{{ $school->name }} &middot; report goes to the {{ $school->directorate() }}</div></div></div>
@include('supervisor._stats')
<form method="POST" action="{{ route('supervisor.inspections.store', $school) }}">@csrf
    <div class="panel">
        <div class="panel-body"><div class="form-grid">
            <div class="field"><label>Date of visit</label><input type="date" name="visit_date" value="{{ old('visit_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required></div>
            <div class="field"><label>Overall rating</label><select name="overall_rating">@foreach ($ratings as $k => $v)<option value="{{ $k }}" @selected(old('overall_rating', 'GOOD') === $k)>{{ $v }}</option>@endforeach</select></div>
            <div class="field full"><label>Findings</label><textarea name="findings" rows="7" required placeholder="Teaching and learning observed, records, attendance, infrastructure, learner welfare">{{ old('findings') }}</textarea></div>
            <div class="field full"><label>Recommendations</label><textarea name="recommendations" rows="5" required>{{ old('recommendations') }}</textarea></div>
            <div class="field"><label class="check"><input type="checkbox" name="flag_follow_up" value="1" @checked(old('flag_follow_up'))> Flag for a follow up visit</label></div>
            <div class="field" data-show-when="flag_follow_up=1"><label>Follow up by</label><input type="date" name="follow_up_by" value="{{ old('follow_up_by') }}"></div>
        </div></div>
        <div class="panel-foot"><span class="small muted">The head teacher and the directorate are notified when you submit.</span><div class="actions"><a class="btn secondary" href="{{ route('supervisor.school', $school) }}">Cancel</a><button class="btn" type="submit">Submit report</button></div></div>
    </div>
</form>
@endsection
