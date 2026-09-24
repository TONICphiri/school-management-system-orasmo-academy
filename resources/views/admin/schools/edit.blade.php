@extends('layouts.app')
@section('title', 'Edit school')
@section('crumbs')<a href="{{ route('admin.schools.index') }}">Schools</a> / <a href="{{ route('admin.schools.show', $school) }}">{{ $school->name }}</a> / @endsection
@section('content')
<div class="page-head"><div><h1>Edit {{ $school->name }}</h1><div class="sub">{{ $school->typeLabel() }} &middot; {{ $school->structure }} structure</div></div></div>
<form method="POST" action="{{ route('admin.schools.update', $school) }}">
    @csrf @method('PUT')
    <div class="panel">
        <div class="panel-body"><div class="form-grid">@include('admin.schools._details')</div></div>
        <div class="panel-foot"><span></span><div class="actions"><a class="btn secondary" href="{{ route('admin.schools.show', $school) }}">Cancel</a><button class="btn" type="submit">Save changes</button></div></div>
    </div>
</form>
@endsection
