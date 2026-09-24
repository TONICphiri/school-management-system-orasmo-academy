<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Learner card {{ $student->learner_uid }}</title>
@vite(['resources/css/app.css'])
</head>
<body class="bg-canvas p-8 print:bg-white print:p-0">
<div class="no-print mb-6 flex justify-center gap-2"><a class="btn secondary" href="{{ route('school.students.show', $student) }}">Back</a><button class="btn" type="button" onclick="window.print()">Print card</button></div>
<div class="mx-auto w-[540px] border border-ink bg-white">
    <div class="flex items-center gap-3 border-b-[5px] border-danger bg-brand-dark px-4 py-[.7rem] text-white">
        <div class="flex-1 text-[.8rem] leading-[1.3]"><strong>Republic of Malawi</strong><div>Ministry of Education</div></div>
        <span class="text-[.8rem] font-bold tracking-[.06em] uppercase">Learner Card</span>
    </div>
    <div class="flex items-start gap-4 p-4">
        <div class="qr">{!! $student->qrSvg(130) !!}</div>
        <dl class="kv m-0 grid-cols-[95px_1fr]! text-[.85rem]">
            <dt>Name</dt><dd><strong>{{ $student->fullName() }}</strong></dd>
            <dt>Learner ID</dt><dd class="learner-uid">{{ $student->learner_uid }}</dd>
            <dt>Gender</dt><dd>{{ $student->gender }}</dd>
            <dt>Date of birth</dt><dd>{{ $student->date_of_birth?->format('j M Y') }}</dd>
            <dt>School</dt><dd>{{ $student->school->name }}</dd>
            <dt>Class</dt><dd>{{ $student->schoolClass?->name() }}</dd>
        </dl>
    </div>
    <div class="border-t border-line px-4 py-2 text-[.72rem] text-muted">If found, return to {{ $student->school->name }}, {{ $student->school->district?->name }} district. This card is valid at any government or registered school in Malawi.</div>
</div>
</body>
</html>
