@php $max = max(1, $summary['total_income'], $summary['total_expenditure']); @endphp
<div class="stats">
    <div class="stat"><div class="label">Income</div><div class="value" style="font-size:1.35rem">{{ mwk($summary['total_income']) }}</div><div class="foot">{{ $term?->label() ?? 'All terms' }}</div></div>
    <div class="stat amber"><div class="label">Expenditure</div><div class="value" style="font-size:1.35rem">{{ mwk($summary['total_expenditure']) }}</div></div>
    <div class="stat {{ $summary['balance'] < 0 ? 'red' : 'blue' }}"><div class="label">Balance</div><div class="value" style="font-size:1.35rem">{{ mwk($summary['balance']) }}</div><div class="foot">{{ $summary['balance'] < 0 ? 'Spending is above income' : 'Funds remaining this term' }}</div></div>
</div>
<div class="grid grid-2">
    <div class="panel">
        <div class="panel-head"><h2>Income by source</h2></div>
        <table class="table">
            <tbody>
            @forelse ($summary['income'] as $r)
                <tr><td>{{ \App\Models\FinanceEntry::INCOME[$r->category] ?? $r->category }}<div class="progress"><span style="width:{{ round($r->total / $max * 100) }}%"></span></div></td><td class="num strong">{{ mwk($r->total) }}</td></tr>
            @empty
                <tr><td class="muted">No income recorded for this term.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="panel">
        <div class="panel-head"><h2>Expenditure by area</h2></div>
        <table class="table">
            <tbody>
            @forelse ($summary['expenditure'] as $r)
                <tr><td>{{ \App\Models\FinanceEntry::EXPENDITURE[$r->category] ?? $r->category }}<div class="progress amber"><span style="width:{{ round($r->total / $max * 100) }}%"></span></div></td><td class="num strong">{{ mwk($r->total) }}</td></tr>
            @empty
                <tr><td class="muted">No expenditure recorded for this term.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
