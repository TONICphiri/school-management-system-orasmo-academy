<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Learner card {{ $student->learner_uid }}</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="print-page">
<div class="print-bar no-print"><a class="btn secondary" href="{{ route('school.students.show', $student) }}">Back</a><button class="btn" type="button" onclick="window.print()">Print card</button></div>
<div class="learner-card">
    <div class="learner-card-head">
        <div><strong>Republic of Malawi</strong><div>Ministry of Education</div></div>
        <span>Learner Card</span>
    </div>
    <div class="learner-card-body">
        <div class="qr">{!! $student->qrSvg(130) !!}</div>
        <dl class="kv">
            <dt>Name</dt><dd><strong>{{ $student->fullName() }}</strong></dd>
            <dt>Learner ID</dt><dd class="learner-uid">{{ $student->learner_uid }}</dd>
            <dt>Gender</dt><dd>{{ $student->gender }}</dd>
            <dt>Date of birth</dt><dd>{{ $student->date_of_birth?->format('j M Y') }}</dd>
            <dt>School</dt><dd>{{ $student->school->name }}</dd>
            <dt>Class</dt><dd>{{ $student->schoolClass?->name() }}</dd>
        </dl>
    </div>
    <div class="learner-card-foot">If found, return to {{ $student->school->name }}, {{ $student->school->district?->name }} district. This card is valid at any government or registered school in Malawi.</div>
</div>
</body>
</html>
