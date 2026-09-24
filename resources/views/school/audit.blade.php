@extends('layouts.app')
@section('title', 'School audit log')
@section('content')
<div class="page-head"><div><h1>School audit log</h1><div class="sub">Sign ins, changes to records, approvals and exports at {{ current_school()?->name }}</div></div></div>
<div class="panel">@include('partials.audit-table')</div>
@endsection
