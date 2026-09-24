<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Committee;
use App\Models\Department;
use App\Models\Level;
use App\Models\School;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TermBreak;
use Carbon\Carbon;

class SchoolSetup
{
    public const PRIMARY_SUBJECTS = [
        ['Chichewa', 'CHI', true], ['English', 'ENG', true], ['Mathematics', 'MAT', true],
        ['Social Studies', 'SOS', true], ['Science and Health', 'SCH', true],
        ['Expressive Arts', 'EXA', true], ['Agriculture', 'AGR', true], ['Religious Education', 'RE', false],
    ];

    public const SECONDARY_SUBJECTS = [
        ['English', 'ENG', true, 'Languages'], ['Chichewa', 'CHI', true, 'Languages'],
        ['Mathematics', 'MAT', true, 'Mathematics'], ['Biology', 'BIO', true, 'Sciences'],
        ['Physical Science', 'PHS', true, 'Sciences'], ['Social Studies', 'SOS', true, 'Humanities'],
        ['Chemistry', 'CHE', false, 'Sciences'], ['Physics', 'PHY', false, 'Sciences'],
        ['History', 'HIS', false, 'Humanities'], ['Geography', 'GEO', false, 'Humanities'],
        ['Bible Knowledge', 'BK', false, 'Humanities'], ['French', 'FRE', false, 'Languages'],
        ['Agriculture', 'AGR', false, 'Practical and Technical'], ['Computer Studies', 'COM', false, 'Practical and Technical'],
        ['Technical Drawing', 'TD', false, 'Practical and Technical'], ['Home Economics', 'HEC', false, 'Practical and Technical'],
    ];

    public const PRIMARY_COMMITTEES = ['Examinations', 'Discipline', 'Sports', 'Entertainment', 'Timetable', 'Welfare'];

    /**
     * @param  array<int,array{starts_on:string,ends_on:string,break_name?:string,break_start?:string,break_end?:string}>  $terms
     */
    public static function initialise(School $school, string $yearName, array $terms): void
    {
        $year = AcademicYear::create([
            'school_id' => $school->id,
            'name' => $yearName,
            'starts_on' => $terms[1]['starts_on'],
            'ends_on' => $terms[3]['ends_on'],
            'is_current' => true,
        ]);

        $today = now()->toDateString();
        $currentSet = false;
        foreach ($terms as $number => $t) {
            $isCurrent = ! $currentSet && $t['ends_on'] >= $today;
            $term = Term::create([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'number' => $number,
                'starts_on' => $t['starts_on'],
                'ends_on' => $t['ends_on'],
                'is_current' => $isCurrent,
            ]);
            $currentSet = $currentSet || $isCurrent;
            if (! empty($t['break_start']) && ! empty($t['break_end'])) {
                TermBreak::create([
                    'school_id' => $school->id,
                    'term_id' => $term->id,
                    'name' => $t['break_name'] ?: 'Mid-term break',
                    'starts_on' => $t['break_start'],
                    'ends_on' => $t['break_end'],
                ]);
            }
        }
        if (! $currentSet) {
            Term::withoutGlobalScopes()->where('academic_year_id', $year->id)->where('number', 1)->update(['is_current' => true]);
        }

        if ($school->hasPrimary()) {
            self::primaryStructure($school);
        }
        if ($school->hasSecondary()) {
            self::secondaryStructure($school);
        }
    }

    protected static function primaryStructure(School $school): void
    {
        $new = $school->structure === '1-6-6-3';
        $levels = $new ? array_merge([0 => 'Preparatory Class'], array_combine(range(1, 6), array_map(fn ($i) => 'Standard '.$i, range(1, 6))))
            : array_combine(range(1, 8), array_map(fn ($i) => 'Standard '.$i, range(1, 8)));
        $examAt = $new ? 6 : 8;

        foreach ($levels as $ordinal => $name) {
            Level::create([
                'school_id' => $school->id,
                'phase' => 'PRIMARY',
                'name' => $name,
                'ordinal' => $ordinal,
                'national_exam' => $ordinal === $examAt ? 'PSLCE' : null,
                'instruction_language' => $ordinal <= 4 ? 'Chichewa' : 'English',
            ]);
        }

        $sections = $new ? [['Infant', 0, 2], ['Junior', 3, 4], ['Senior', 5, 6]] : [['Infant', 1, 2], ['Junior', 3, 5], ['Senior', 6, 8]];
        foreach ($sections as [$name, $from, $to]) {
            Section::create(['school_id' => $school->id, 'name' => $name, 'from_ordinal' => $from, 'to_ordinal' => $to]);
        }

        foreach (self::PRIMARY_SUBJECTS as [$name, $code, $core]) {
            Subject::create([
                'school_id' => $school->id, 'phase' => 'PRIMARY', 'name' => $name, 'code' => $code,
                'is_core' => $core, 'is_optional' => ! $core, 'ca_weight' => 40, 'exam_weight' => 60,
            ]);
        }

        foreach (self::PRIMARY_COMMITTEES as $name) {
            Committee::create(['school_id' => $school->id, 'name' => $name]);
        }
    }

    protected static function secondaryStructure(School $school): void
    {
        $forms = $school->structure === '1-6-6-3' ? 6 : 4;
        foreach (range(1, $forms) as $i) {
            $exam = $forms === 4 ? ($i === 2 ? 'JCE' : ($i === 4 ? 'MSCE' : null)) : ($i === 6 ? 'MSCE' : null);
            Level::create([
                'school_id' => $school->id, 'phase' => 'SECONDARY', 'name' => 'Form '.$i,
                'ordinal' => $i, 'national_exam' => $exam, 'instruction_language' => 'English',
            ]);
        }

        $departments = [];
        foreach (['Sciences', 'Humanities', 'Languages', 'Mathematics', 'Practical and Technical'] as $name) {
            $departments[$name] = Department::create(['school_id' => $school->id, 'name' => $name])->id;
        }

        foreach (self::SECONDARY_SUBJECTS as [$name, $code, $core, $dept]) {
            Subject::create([
                'school_id' => $school->id, 'department_id' => $departments[$dept], 'phase' => 'SECONDARY',
                'name' => $name, 'code' => $code, 'is_core' => $core, 'is_optional' => false,
                'ca_weight' => 40, 'exam_weight' => 60,
            ]);
        }
    }

    /**
     * Suggested Malawi calendar for a year starting in September.
     */
    public static function defaultTerms(int $startYear): array
    {
        $y = $startYear;
        $n = $startYear + 1;

        return [
            1 => ['starts_on' => "$y-09-14", 'ends_on' => "$y-12-18", 'break_name' => 'Mid-term break', 'break_start' => "$y-10-29", 'break_end' => "$y-11-01"],
            2 => ['starts_on' => "$n-01-04", 'ends_on' => "$n-03-25", 'break_name' => 'Mid-term break', 'break_start' => "$n-02-18", 'break_end' => "$n-02-21"],
            3 => ['starts_on' => "$n-04-12", 'ends_on' => "$n-07-16", 'break_name' => 'Mid-term break', 'break_start' => "$n-05-27", 'break_end' => "$n-05-30"],
        ];
    }
}
