<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\Term;
use App\Services\TermSummary;
use Illuminate\Console\Command;

class SendTermSummaries extends Command
{
    protected $signature = 'sms:term-summaries {--force : Send for every active school today}';

    protected $description = 'Send the end of term summary to SMC, PTA and Board of Governors members';

    public function handle(): int
    {
        $schools = School::where('status', 'ACTIVE')->get();
        $sent = 0;
        foreach ($schools as $school) {
            $term = Term::withoutGlobalScopes()->where('school_id', $school->id)->where('is_current', true)->first();
            if (! $term) {
                continue;
            }
            $dueToday = $term->ends_on->copy()->subDays(7)->isToday();
            if ($dueToday || $this->option('force')) {
                $sent += TermSummary::send($school);
            }
        }
        $this->info("Summaries sent to $sent governance members.");

        return self::SUCCESS;
    }
}
