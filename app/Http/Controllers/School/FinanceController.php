<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\FinanceEntry;
use App\Models\Term;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $terms = Term::with('academicYear')->orderByDesc('starts_on')->get();
        $term = $request->filled('term_id') ? $terms->firstWhere('id', (int) $request->query('term_id')) : current_term();
        $canRecord = $user->isSchoolAdmin();

        return view('school.finance.index', [
            'term' => $term,
            'terms' => $terms,
            'summary' => FinanceEntry::summary(current_school()->id, $term?->id),
            // Governance members see the summary only. Entries with descriptions are for the school office.
            'entries' => $user->role === 'GOVERNANCE' ? collect() : FinanceEntry::with('recorder')->where('term_id', $term?->id)->latest('entry_date')->latest('id')->get(),
            'canRecord' => $canRecord,
            'readOnly' => $user->role === 'GOVERNANCE',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:INCOME,EXPENDITURE',
            'category' => ['required', Rule::in(array_keys(FinanceEntry::INCOME + FinanceEntry::EXPENDITURE))],
            'description' => 'required|string|max:200',
            'amount' => 'required|numeric|min:1|max:999999999',
            'entry_date' => 'required|date|before_or_equal:today',
            'reference' => 'nullable|string|max:40',
        ]);
        $isIncome = array_key_exists($data['category'], FinanceEntry::INCOME);
        if ($isIncome !== ($data['type'] === 'INCOME')) {
            return back()->withInput()->withErrors(['category' => 'That category does not match the entry type.']);
        }
        $term = Term::where('starts_on', '<=', $data['entry_date'])->where('ends_on', '>=', $data['entry_date'])->first() ?? current_term();
        $entry = FinanceEntry::create($data + ['term_id' => $term?->id, 'recorded_by' => $request->user()->id]);
        Audit::log('finance.recorded', 'Recorded '.strtolower($data['type']).' of '.mwk($data['amount']).' for '.$entry->categoryLabel(), $entry);

        return back()->with('status', 'Entry of '.mwk($data['amount']).' recorded.');
    }

    public function destroy(FinanceEntry $entry)
    {
        Audit::log('finance.removed', 'Removed '.strtolower($entry->type).' of '.mwk($entry->amount).' ('.$entry->description.')', $entry);
        $entry->delete();

        return back()->with('status', 'Entry removed.');
    }
}
