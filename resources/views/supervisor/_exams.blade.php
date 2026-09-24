<div class="panel">
    <div class="panel-head"><h2>National examination readiness</h2><span class="hint">From this term's school results</span></div>
    <table class="table">
        <thead><tr><th>Examination</th><th class="num">Candidates</th><th class="num">With marks</th><th class="num">Meeting the standard</th><th>Rate</th><th class="num">Girls meeting it</th></tr></thead>
        <tbody>
        @forelse ($exams as $e)
            <tr>
                <td><strong>{{ $e['exam'] }}</strong><div class="small muted">{{ ['PSLCE' => 'Standard 8, average of 40 or more', 'JCE' => 'Form 2, six passes including English', 'MSCE' => 'Form 4, six credits including English'][$e['exam']] ?? '' }}</div></td>
                <td class="num">{{ $e['candidates'] }}</td>
                <td class="num">{{ $e['with_marks'] }}</td>
                <td class="num">{{ $e['eligible'] }}</td>
                <td class="min-w-[140px]"><div class="progress {{ pct_tone($e['rate'], 60, 40) }}"><span style="width:{{ (int) $e['rate'] }}%"></span></div><span class="small">{{ num($e['rate']) }}{{ $e['rate'] !== null ? '%' : '' }}</span></td>
                <td class="num">{{ $e['girls_eligible'] }} of {{ $e['girls'] }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">No examination classes this year.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
