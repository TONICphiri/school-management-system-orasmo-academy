@extends('layouts.app')
@section('title', 'Schools')
@section('content')
<div class="page-head">
    <div><h1>Schools</h1><div class="sub">{{ $schools->total() }} registered</div></div>
    <div class="actions"><a class="btn" href="{{ route('admin.schools.create') }}">{{ icon('plus', 16) }} Register a school</a></div>
</div>
<div class="panel">
    <form class="panel-body filters" method="GET">
        <div class="field"><label>Search</label><input type="search" name="q" value="{{ request('q') }}" placeholder="Name or code"></div>
        <div class="field"><label>Division</label><select name="division_id"><option value="">All</option>@foreach ($divisions as $d)<option value="{{ $d->id }}" @selected(request('division_id') == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
        <div class="field"><label>District</label><select name="district_id"><option value="">All</option>@foreach ($districts as $d)<option value="{{ $d->id }}" data-parent="{{ $d->division_id }}" @selected(request('district_id') == $d->id)>{{ $d->name }}</option>@endforeach</select></div>
        <div class="field"><label>Type</label><select name="type"><option value="">All</option>@foreach (\App\Models\School::TYPES as $k => $v)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="field"><label>Status</label><select name="status"><option value="">All</option>@foreach (\App\Models\School::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select></div>
        <div class="actions"><button class="btn secondary" type="submit">{{ icon('search', 16) }} Filter</button><a class="btn ghost" href="{{ route('admin.schools.index') }}">Clear</a></div>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>School</th><th>Type</th><th>Category</th><th>District</th><th>Division</th><th>Head teacher</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($schools as $s)
                <tr>
                    <td><a class="strong" href="{{ route('admin.schools.show', $s) }}">{{ $s->name }}</a><div class="small muted">{{ $s->code }}</div></td>
                    <td>{{ $s->typeLabel() }}</td>
                    <td>{{ $s->categoryLabel() }}</td>
                    <td>{{ $s->district->name }}</td>
                    <td>{{ $s->division->name }}</td>
                    <td>{{ $s->headTeacher?->name ?? 'Not assigned' }}</td>
                    <td><span class="badge {{ status_tone($s->status) }}">{{ $s->statusLabel() }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty"><strong>No schools match</strong>Change the filters or register a new school.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $schools->links() }}
</div>
@endsection
