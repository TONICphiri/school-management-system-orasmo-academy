<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class FinanceEntry extends Model
{
    use BelongsToSchool;
    protected $guarded = [];
    protected $casts = ['entry_date' => 'date', 'amount' => 'decimal:2'];

    public const INCOME = [
        'SIG' => 'School Improvement Grant',
        'ORT' => 'Government funding (ORT)',
        'FEES' => 'School fees',
        'DEVELOPMENT' => 'Development fund',
        'PTA' => 'PTA contributions',
        'DONATION' => 'Donations and partners',
        'OTHER_INCOME' => 'Other income',
    ];

    public const EXPENDITURE = [
        'TLM' => 'Teaching and learning materials',
        'MAINTENANCE' => 'Buildings and maintenance',
        'EXAMS' => 'Examinations',
        'UTILITIES' => 'Water and electricity',
        'FEEDING' => 'School feeding',
        'SPORTS' => 'Sports and clubs',
        'SALARIES' => 'Salaries and allowances',
        'ADMIN' => 'Administration and stationery',
        'OTHER_EXPENSE' => 'Other expenditure',
    ];

    public function term() { return $this->belongsTo(Term::class); }
    public function recorder() { return $this->belongsTo(User::class, 'recorded_by'); }

    public function categoryLabel(): string
    {
        return self::INCOME[$this->category] ?? self::EXPENDITURE[$this->category] ?? $this->category;
    }

    /** Income, expenditure and balance for a school and term, by category. Read without the tenant scope. */
    public static function summary(int $schoolId, ?int $termId): array
    {
        $rows = self::withoutGlobalScopes()->where('school_id', $schoolId)->when($termId, fn ($q) => $q->where('term_id', $termId))
            ->selectRaw('type, category, sum(amount) as total, count(*) as entries')->groupBy('type', 'category')->get();
        $income = $rows->where('type', 'INCOME')->sortByDesc('total')->values();
        $spend = $rows->where('type', 'EXPENDITURE')->sortByDesc('total')->values();

        return [
            'income' => $income,
            'expenditure' => $spend,
            'total_income' => (float) $income->sum('total'),
            'total_expenditure' => (float) $spend->sum('total'),
            'balance' => (float) $income->sum('total') - (float) $spend->sum('total'),
        ];
    }
}
