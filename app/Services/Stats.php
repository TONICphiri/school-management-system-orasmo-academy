<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\ClassSubject;
use App\Models\School;
use App\Models\Student;
use App\Models\SubjectResultStatus;
use App\Models\User;

class Stats
{
    /**
     * Headline indicators for one school, read without the tenant scope so
     * supervisors and national users can call it.
     */
    public static function school(School $school): array
    {
        $term = $school->currentTerm();
        $learners = Student::withoutGlobalScopes()->where('school_id', $school->id)->where('status', 'ENROLLED');
        $enrolment = (clone $learners)->count();
        $girls = (clone $learners)->where('gender', 'Female')->count();
        $teachers = User::where('school_id', $school->id)->whereIn('role', User::TEACHING_ROLES)->where('status', 'ACTIVE')->count();
        $qualified = User::where('school_id', $school->id)->whereIn('role', User::TEACHING_ROLES)
            ->whereHas('teacherProfile', fn ($q) => $q->whereIn('qualification', $school->type === 'PRIMARY' ? ['T2', 'DIPLOMA', 'BACHELOR', 'MASTERS'] : ['DIPLOMA', 'BACHELOR', 'MASTERS']))
            ->count();

        $attendance = null;
        $completion = null;
        $passRate = null;
        if ($term) {
            $att = Attendance::withoutGlobalScopes()->where('school_id', $school->id)
                ->whereBetween('attended_on', [$term->starts_on, $term->ends_on]);
            $total = (clone $att)->count();
            $attendance = $total ? round((clone $att)->where('status', 'PRESENT')->count() / $total * 100, 1) : null;

            $lessonCount = ClassSubject::withoutGlobalScopes()->where('school_id', $school->id)->count();
            $done = SubjectResultStatus::withoutGlobalScopes()->where('school_id', $school->id)
                ->where('term_id', $term->id)->whereIn('status', ['SUBMITTED', 'VALIDATED'])->count();
            $completion = $lessonCount ? round($done / $lessonCount * 100, 1) : null;

            $examIds = Assessment::withoutGlobalScopes()->where('school_id', $school->id)
                ->where('term_id', $term->id)->where('kind', 'EXAM')->pluck('max_score', 'id');
            if ($examIds->isEmpty()) {
                // Early in the term there are no examinations yet, so use continuous assessment
                $examIds = Assessment::withoutGlobalScopes()->where('school_id', $school->id)
                    ->where('term_id', $term->id)->pluck('max_score', 'id');
            }
            if ($examIds->count()) {
                $marks = \App\Models\Mark::withoutGlobalScopes()->whereIn('assessment_id', $examIds->keys())
                    ->where('absent', false)->whereNotNull('score')->get(['assessment_id', 'score']);
                $passed = $marks->filter(fn ($m) => $m->score / max(1, $examIds[$m->assessment_id]) * 100 >= 40)->count();
                $passRate = $marks->count() ? round($passed / $marks->count() * 100, 1) : null;
            }
        }

        return [
            'enrolment' => $enrolment,
            'girls' => $girls,
            'boys' => $enrolment - $girls,
            'teachers' => $teachers,
            'qualified' => $qualified,
            'ptr' => $teachers ? round($enrolment / $teachers, 1) : null,
            'attendance' => $attendance,
            'completion' => $completion,
            'pass_rate' => $passRate,
            'term' => $term,
        ];
    }

    /**
     * Schools inside a supervisor's jurisdiction.
     */
    public static function jurisdiction(User $user)
    {
        $q = School::query()->with(['district', 'division', 'zone']);

        return match ($user->role) {
            'EDM' => $q->where('division_id', $user->division_id)->whereIn('type', ['SECONDARY', 'COMBINED']),
            'DEM' => $q->where('district_id', $user->district_id)->whereIn('type', ['PRIMARY', 'COMBINED']),
            'PEA' => $q->where('zone_id', $user->zone_id)->whereIn('type', ['PRIMARY', 'COMBINED']),
            'SYSTEM_ADMIN' => $q,
            default => $q->whereRaw('1 = 0'),
        };
    }

    public static function canSupervise(User $user, School $school): bool
    {
        return self::jurisdiction($user)->whereKey($school->id)->exists();
    }

    /**
     * National examination readiness for one school in the current term, based on the school's
     * own results: PSLCE (Standard 8 average of 40 or more), JCE (six passes including English)
     * and MSCE (six credits including English). Read without the tenant scope for supervisors.
     */
    public static function examPerformance(School $school): array
    {
        $term = $school->currentTerm();
        $year = $school->currentYear();
        if (! $term || ! $year) {
            return [];
        }
        $levels = \App\Models\Level::withoutGlobalScopes()->where('school_id', $school->id)->whereNotNull('national_exam')->get()->keyBy('id');
        $classes = \App\Models\SchoolClass::withoutGlobalScopes()->with(['level' => fn ($q) => $q->withoutGlobalScopes(), 'classSubjects' => fn ($q) => $q->withoutGlobalScopes()->with(['subject' => fn ($s) => $s->withoutGlobalScopes()])])
            ->where('school_id', $school->id)->where('academic_year_id', $year->id)->whereIn('level_id', $levels->keys())->get();

        $out = [];
        foreach ($classes as $class) {
            $exam = $class->level->national_exam;
            $out[$exam] ??= ['exam' => $exam, 'candidates' => 0, 'with_marks' => 0, 'eligible' => 0, 'girls' => 0, 'girls_eligible' => 0];
            $results = \App\Services\Grading::classResults($class, $term);
            foreach ($results['learners'] as $l) {
                $row = &$out[$exam];
                $row['candidates']++;
                $girl = $l['student']->gender === 'Female';
                $row['girls'] += $girl ? 1 : 0;
                if ($l['average'] === null) {
                    unset($row);
                    continue;
                }
                $row['with_marks']++;
                $ok = $exam === 'PSLCE' ? $l['average'] >= 40 : (bool) ($l['exam_eligible'] ?? false);
                $row['eligible'] += $ok ? 1 : 0;
                $row['girls_eligible'] += ($ok && $girl) ? 1 : 0;
                unset($row);
            }
        }
        foreach ($out as &$row) {
            $row['rate'] = $row['with_marks'] ? round($row['eligible'] / $row['with_marks'] * 100, 1) : null;
            $row['girls_rate'] = $row['girls'] && $row['with_marks'] ? round($row['girls_eligible'] / max(1, $row['girls']) * 100, 1) : null;
        }
        unset($row);
        $order = ['PSLCE' => 1, 'JCE' => 2, 'MSCE' => 3];
        uksort($out, fn ($a, $b) => ($order[$a] ?? 9) <=> ($order[$b] ?? 9));

        return $out;
    }
}
