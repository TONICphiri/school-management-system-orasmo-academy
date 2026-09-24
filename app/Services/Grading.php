<?php

namespace App\Services;

use App\Models\ClassSubject;
use App\Models\GradeBand;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use Illuminate\Support\Collection;

class Grading
{
    protected static array $bands = [];

    public static function bands(string $phase): Collection
    {
        return self::$bands[$phase] ??= GradeBand::where('phase', $phase)->orderBy('sort_order')->get();
    }

    public static function band(string $phase, ?float $score): ?GradeBand
    {
        if ($score === null) {
            return null;
        }
        $rounded = (int) round($score);

        return self::bands($phase)->first(fn ($b) => $rounded >= $b->min_score && $rounded <= $b->max_score);
    }

    /**
     * Scores for one class subject in a term, keyed by student id.
     * Each row holds ca, exam, final (all out of 100), grade and label.
     */
    public static function subjectScores(ClassSubject $cs, Term $term): array
    {
        $cs->loadMissing(['subject', 'schoolClass.level']);
        $phase = $cs->schoolClass->level->phase;
        $assessments = $cs->assessments()->where('term_id', $term->id)->with('marks')->get();
        $ca = $assessments->where('kind', 'CA');
        $exam = $assessments->where('kind', 'EXAM');
        $rows = [];

        foreach ($cs->roster() as $student) {
            $caPct = self::percent($ca, $student->id);
            $examPct = self::percent($exam, $student->id);
            $final = self::combine($caPct, $examPct, $cs->subject->ca_weight, $cs->subject->exam_weight);
            $band = self::band($phase, $final);
            $rows[$student->id] = [
                'student' => $student,
                'ca' => $caPct,
                'exam' => $examPct,
                'final' => $final,
                'grade' => $band?->grade,
                'label' => $band?->label,
            ];
        }

        return $rows;
    }

    protected static function percent(Collection $assessments, int $studentId): ?float
    {
        $got = 0;
        $max = 0;
        foreach ($assessments as $a) {
            $mark = $a->marks->firstWhere('student_id', $studentId);
            if ($mark && ! $mark->absent && $mark->score !== null) {
                $got += (float) $mark->score;
                $max += $a->max_score;
            }
        }

        return $max > 0 ? round($got / $max * 100, 1) : null;
    }

    public static function combine(?float $ca, ?float $exam, int $caWeight = 40, int $examWeight = 60): ?float
    {
        if ($ca === null && $exam === null) {
            return null;
        }
        if ($ca === null) {
            return $exam;
        }
        if ($exam === null) {
            return $ca;
        }

        return round(($ca * $caWeight + $exam * $examWeight) / max(1, $caWeight + $examWeight), 1);
    }

    /**
     * Full class results for a term: per learner subject rows, average,
     * position and, for secondary, best six points and exam eligibility.
     */
    public static function classResults(SchoolClass $class, Term $term): array
    {
        $class->loadMissing(['level', 'classSubjects.subject']);
        $phase = $class->level->phase;
        $subjects = $class->classSubjects->sortBy(fn ($cs) => [$cs->subject->is_core ? 0 : 1, $cs->subject->name])->values();
        $perSubject = [];
        foreach ($subjects as $cs) {
            $perSubject[$cs->id] = self::subjectScores($cs, $term);
        }

        $learners = [];
        foreach ($class->students()->where('status', 'ENROLLED')->get() as $student) {
            $rows = [];
            foreach ($subjects as $cs) {
                if (isset($perSubject[$cs->id][$student->id])) {
                    $rows[$cs->id] = $perSubject[$cs->id][$student->id] + ['subject' => $cs->subject];
                }
            }
            $finals = collect($rows)->pluck('final')->filter(fn ($v) => $v !== null);
            $learners[$student->id] = [
                'student' => $student,
                'rows' => $rows,
                'average' => $finals->count() ? round($finals->avg(), 1) : null,
                'total' => $finals->count() ? round($finals->sum(), 1) : null,
            ] + ($phase === 'SECONDARY' ? self::secondaryStanding($rows, $class->level->national_exam) : []);
        }

        $sorted = collect($learners)->sortByDesc(fn ($l) => $l['average'] ?? -1)->values();
        $position = 0;
        $previous = null;
        foreach ($sorted as $i => $l) {
            if ($l['average'] !== $previous) {
                $position = $i + 1;
                $previous = $l['average'];
            }
            $learners[$l['student']->id]['position'] = $l['average'] === null ? null : $position;
        }

        return [
            'subjects' => $subjects,
            'learners' => $learners,
            'class_size' => count($learners),
            'class_average' => collect($learners)->pluck('average')->filter(fn ($v) => $v !== null)->avg(),
        ];
    }

    protected static function secondaryStanding(array $rows, ?string $exam): array
    {
        $graded = collect($rows)->filter(fn ($r) => $r['grade'] !== null);
        $points = $graded->pluck('grade')->map(fn ($g) => (int) $g)->sort()->values();
        $english = $graded->first(fn ($r) => $r['subject']->name === 'English');
        $englishGrade = $english ? (int) $english['grade'] : 9;

        $passes = $points->filter(fn ($p) => $p <= 8)->count();
        $credits = $points->filter(fn ($p) => $p <= 6)->count();

        $eligible = null;
        $note = null;
        if ($exam === 'JCE') {
            $eligible = $passes >= 6 && $englishGrade <= 8;
            $note = $eligible ? 'Meets JCE standard: six passes including English' : 'Below JCE standard: needs six passes including English';
        } elseif ($exam === 'MSCE') {
            $eligible = $credits >= 6 && $englishGrade <= 6;
            $note = $eligible ? 'On course for MSCE: six credits including English' : 'Not yet at six credits including English';
        }

        return [
            'best_six' => $points->count() >= 6 ? $points->take(6)->sum() : null,
            'passes' => $passes,
            'credits' => $credits,
            'exam_eligible' => $eligible,
            'exam_note' => $note,
        ];
    }

    public static function studentReport(Student $student, Term $term): ?array
    {
        if (! $student->schoolClass) {
            return null;
        }
        $results = self::classResults($student->schoolClass, $term);
        if (! isset($results['learners'][$student->id])) {
            return null;
        }

        return $results['learners'][$student->id] + ['class_size' => $results['class_size'], 'class_average' => $results['class_average']];
    }
}
