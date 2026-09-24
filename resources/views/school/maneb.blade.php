@extends('layouts.app')
@section('title', 'MANEB candidates')
@section('content')
@php $leader = auth()->user()->isSchoolLeader(); @endphp
<div class="page-head">
    <div><h1>MANEB candidate register</h1><div class="sub">{{ $school->name }} &middot; centre {{ $school->maneb_centre_number ?: 'not set' }} &middot; {{ count($rows) }} candidates</div></div>
</div>
<div class="tabs">
    <a href="{{ route('school.maneb.index') }}" class="{{ ! $exam ? 'active' : '' }}">All examinations</a>
    @foreach ($exams as $e)<a href="{{ route('school.maneb.index', ['exam' => $e]) }}" class="{{ $exam === $e ? 'active' : '' }}">{{ $e }}</a>@endforeach
</div>
<div class="grid grid-main">
    <form method="POST" action="{{ route('school.maneb.numbers') }}">@csrf
        <div class="panel">
            <div class="panel-head"><h2>Candidates</h2>@if($leader)<div class="inline-form"><label class="small strong" for="centre">Centre number</label><input class="min-w-[110px] w-[110px]" id="centre" type="text" name="centre_number" value="{{ $school->maneb_centre_number }}"></div>@endif</div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Exam</th><th>Candidate</th><th>Sex</th><th>Class</th><th>Examination number</th><th class="num">Subjects</th><th>School recommendation</th></tr></thead>
                <tbody>
                @forelse ($rows as $r)
                    @php $s = $r['student']; @endphp
                    <tr>
                        <td><span class="badge info">{{ $r['exam'] }}</span></td>
                        <td><span class="strong">{{ strtoupper($s->last_name) }}</span> {{ $s->first_name }}<div class="small muted">{{ $s->admission_number }}</div></td>
                        <td>{{ $s->gender === 'Female' ? 'F' : 'M' }}</td>
                        <td>{{ $r['class']->name() }}</td>
                        <td>@if($leader)<input class="w-[150px]" type="text" name="numbers[{{ $s->id }}]" value="{{ $s->maneb_exam_number }}">@else{{ $s->maneb_exam_number ?: 'Not issued' }}@endif</td>
                        <td class="num" title="{{ $r['subjects']->pluck('name')->implode(', ') }}">{{ $r['subjects']->count() }}</td>
                        <td><span class="badge {{ $r['eligible'] ? 'ok' : 'warn' }}">{{ $r['eligible'] ? 'Eligible' : 'Review' }}</span><div class="small muted">{{ $r['note'] }}</div></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty"><strong>No candidates</strong>Learners in examination classes (Standard 8, Form 2 and Form 4) appear here.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            @if($leader && count($rows))<div class="panel-foot"><span class="small muted">Numbers are issued by MANEB. Changes are recorded in the audit log.</span><button class="btn secondary" type="submit">Save numbers</button></div>@endif
        </div>
    </form>
    <div class="stack">
        <div class="panel">
            <div class="panel-head"><h2>Export for MANEB</h2>{{ icon('lock', 16) }}</div>
            <form method="POST" action="{{ route('school.maneb.export') }}">@csrf
                <div class="panel-body stack-sm">
                    <div class="field"><label>Examination</label><select name="exam">@foreach ($exams as $e)<option @selected($exam === $e)>{{ $e }}</option>@endforeach</select></div>
                    <div class="field"><label>File password</label><input type="password" name="password" minlength="8" required autocomplete="new-password"><div class="help">At least 8 characters. Share it with the MANEB officer by phone, not in the same email.</div></div>
                    @unless ($zipEncryption)<div class="alert warn m-0">Enable <strong>extension=zip</strong> in php.ini for AES encrypted exports.</div>@endunless
                </div>
                <div class="panel-foot"><span class="small muted">Each export is logged.</span><button class="btn" type="submit">{{ icon('download', 16) }} Download encrypted file</button></div>
            </form>
        </div>
        <div class="panel">
            <div class="panel-head"><h2>Entry requirements</h2></div>
            <div class="panel-body small">
                <p><strong>PSLCE</strong> Standard 8 learners sit English, Chichewa, Mathematics, Primary Science, Social and Environmental Sciences, and Expressive Arts.</p>
                <p><strong>JCE</strong> Six passes including English.</p>
                <p class="mb-0"><strong>MSCE</strong> Six credits including English. Grades 1 and 2 are distinctions, 3 to 6 credits, 7 and 8 passes and 9 a fail.</p>
            </div>
        </div>
    </div>
</div>
@endsection
