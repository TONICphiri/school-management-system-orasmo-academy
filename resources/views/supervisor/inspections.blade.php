@extends('layouts.app')
@section('title', 'Inspection reports')
@section('content')
<div class="page-head"><div><h1>Inspection reports</h1><div class="sub">Visits to schools in {{ auth()->user()->scopeLabel() }}. Record a new visit from the school page.</div></div></div>
<div class="panel">@include('partials.inspection-table')</div>
@endsection
