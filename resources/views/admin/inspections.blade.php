@extends('layouts.app')
@section('title', 'Inspection reports')
@section('content')
<div class="page-head"><div><h1>Inspection reports</h1><div class="sub">Reports from supervisors, routed to the Directorate of Basic Education or the Directorate of Secondary Education</div></div></div>
<div class="panel">@include('partials.inspection-table')</div>
@endsection
