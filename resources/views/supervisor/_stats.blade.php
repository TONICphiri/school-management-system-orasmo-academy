<div class="stats">
    <div class="stat"><div class="label">Learners</div><div class="value">{{ $stats['enrolment'] }}</div><div class="foot">{{ $stats['girls'] }} girls, {{ $stats['boys'] }} boys</div></div>
    <div class="stat {{ ($stats['ptr'] ?? 0) > 60 ? 'red' : '' }}"><div class="label">Pupil teacher ratio</div><div class="value">{{ num($stats['ptr']) }}<small>:1</small></div><div class="foot">{{ $stats['teachers'] }} teachers, {{ $stats['qualified'] }} qualified</div></div>
    <div class="stat {{ pct_tone($stats['attendance'], 90, 80) }}"><div class="label">Attendance</div><div class="value">{{ num($stats['attendance']) }}<small>%</small></div></div>
    <div class="stat blue"><div class="label">Marks submitted</div><div class="value">{{ num($stats['completion']) }}<small>%</small></div></div>
    <div class="stat {{ pct_tone($stats['pass_rate'], 70, 50) }}"><div class="label">Pass rate</div><div class="value">{{ num($stats['pass_rate']) }}<small>%</small></div></div>
</div>
