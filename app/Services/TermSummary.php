<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;

class TermSummary
{
    public static function send(School $school): int
    {
        $stats = Stats::school($school);
        $term = $stats['term'];
        if (! $term) {
            return 0;
        }

        $body = $school->name.', '.$term->label().'. '
            .'Enrolment: '.$stats['enrolment'].' ('.$stats['girls'].' girls, '.$stats['boys'].' boys). '
            .'Teachers: '.$stats['teachers'].', learners per teacher: '.num($stats['ptr']).'. '
            .'Attendance: '.num($stats['attendance']).'%. '
            .'Marks submitted: '.num($stats['completion']).'% of subjects. '
            .'End of term pass rate: '.num($stats['pass_rate']).'%.';

        $members = User::where('school_id', $school->id)->where('role', 'GOVERNANCE')->where('status', 'ACTIVE')->get();
        $count = Notifier::send($members, 'REPORT', 'Term summary for '.$term->label(), $body, route('dashboard'), 'NORMAL', ['APP', 'PREFERRED']);

        \App\Models\AuditLog::create([
            'school_id' => $school->id,
            'user_id' => auth()->id(),
            'action' => 'report.term_summary',
            'target_type' => 'Term',
            'target_id' => $term->id,
            'description' => 'Term summary sent to '.$count.' governance members',
            'created_at' => now(),
        ]);

        return $count;
    }
}
