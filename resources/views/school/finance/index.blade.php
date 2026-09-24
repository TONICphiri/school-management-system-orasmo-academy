@extends('layouts.app')
@section('title', 'School finances')
@section('content')
<div class="page-head">
    <div><h1>{{ $readOnly ? 'Financial summary' : 'Income and expenditure' }}</h1><div class="sub">{{ $readOnly ? 'Read only summary for SMC, PTA and Board members. Figures are in Malawi Kwacha.' : 'School Improvement Grant, government funding, fees and PTA contributions, and how they were spent' }}</div></div>
    <form method="GET" class="inline-form no-print">
        <select name="term_id" onchange="this.form.submit()">@foreach ($terms as $t)<option value="{{ $t->id }}" @selected($term?->id === $t->id)>{{ $t->label() }}</option>@endforeach</select>
        <button type="button" class="btn secondary" onclick="window.print()">{{ icon('printer', 16) }} Print</button>
    </form>
</div>

@include('school.finance._summary')

@if ($canRecord)
<div class="panel no-print">
    <div class="panel-head"><h2>Record an entry</h2><span class="hint">Every entry is written to the audit log</span></div>
    <form method="POST" action="{{ route('school.finance.store') }}">@csrf
        <div class="panel-body"><div class="form-grid">
            <div class="field"><label>Type</label><select name="type"><option value="INCOME" @selected(old('type') === 'INCOME')>Income</option><option value="EXPENDITURE" @selected(old('type') === 'EXPENDITURE')>Expenditure</option></select></div>
            <div class="field"><label>Category</label><select name="category">
                <optgroup label="Income">@foreach (\App\Models\FinanceEntry::INCOME as $k => $v)<option value="{{ $k }}" @selected(old('category') === $k)>{{ $v }}</option>@endforeach</optgroup>
                <optgroup label="Expenditure">@foreach (\App\Models\FinanceEntry::EXPENDITURE as $k => $v)<option value="{{ $k }}" @selected(old('category') === $k)>{{ $v }}</option>@endforeach</optgroup>
            </select></div>
            <div class="field"><label>Amount (MK)</label><input type="number" name="amount" min="1" step="1" value="{{ old('amount') }}" required></div>
            <div class="field"><label>Date</label><input type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" required></div>
            <div class="field"><label>Receipt or voucher number</label><input type="text" name="reference" value="{{ old('reference') }}" placeholder="RV 0214"></div>
            <div class="field full"><label>Description</label><input type="text" name="description" value="{{ old('description') }}" placeholder="Purchase of 40 reams of paper for end of term examinations" required></div>
        </div></div>
        <div class="panel-foot"><span></span><button class="btn" type="submit">Save entry</button></div>
    </form>
</div>
@endif

@unless ($readOnly)
<div class="panel">
    <div class="panel-head"><h2>Entries this term</h2><span class="hint">{{ $entries->count() }} entries</span></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Reference</th><th class="num">Amount</th><th>Recorded by</th>@if($canRecord)<th></th>@endif</tr></thead>
        <tbody>
        @forelse ($entries as $e)
            <tr>
                <td>{{ $e->entry_date->format('j M Y') }}</td>
                <td><span class="badge {{ $e->type === 'INCOME' ? 'ok' : 'warn' }}">{{ $e->type === 'INCOME' ? 'Income' : 'Spent' }}</span> {{ $e->categoryLabel() }}</td>
                <td>{{ $e->description }}</td>
                <td>{{ $e->reference }}</td>
                <td class="num strong">{{ mwk($e->amount) }}</td>
                <td class="small">{{ $e->recorder?->name }}</td>
                @if($canRecord)<td><form method="POST" action="{{ route('school.finance.destroy', $e) }}" onsubmit="return confirm('Remove this entry?')">@csrf @method('DELETE')<button class="btn ghost small" type="submit">Remove</button></form></td>@endif
            </tr>
        @empty
            <tr><td colspan="7" class="muted">No entries for this term.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
@endunless
@endsection
