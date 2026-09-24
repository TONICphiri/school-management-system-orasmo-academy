@extends('layouts.app')
@section('title', 'Mark entry')
@section('content')
<div class="page-head"><div><h1>Mark entry</h1><div class="sub">{{ $term?->label() ?? 'No current term' }} &middot; continuous assessment carries 40% and the end of term examination 60%</div></div></div>
<div class="panel">
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Class</th><th>Subject</th><th>Teacher</th><th class="num">Assessments</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($lessons as $row)
            @php $l = $row['lesson']; $st = $row['status']; @endphp
            <tr>
                <td class="strong">{{ $l->schoolClass->name() }}</td>
                <td>{{ $l->subject->name }}</td>
                <td>{{ $l->teacher?->name ?? 'Not assigned' }}</td>
                <td class="num">{{ $row['assessments'] }}</td>
                <td>@if($st)<span class="badge {{ status_tone($st->status) }}">{{ label($st->status) }}</span>@if($st->status === 'RETURNED' && $st->remark)<div class="small muted">{{ $st->remark }}</div>@endif @endif</td>
                <td class="right"><a class="btn {{ $st && in_array($st->status, ['DRAFT', 'RETURNED']) ? '' : 'secondary' }} small" href="{{ route('school.marks.show', $l) }}">{{ $st && in_array($st->status, ['DRAFT', 'RETURNED']) ? 'Enter marks' : 'View' }}</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty"><strong>No subjects assigned to you</strong>The head teacher assigns subjects on the class page.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
@endsection
