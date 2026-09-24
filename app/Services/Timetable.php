<?php

namespace App\Services;

use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\TimetableSlot;

class Timetable
{
    public const PERIODS = 8;

    public const PERIOD_TIMES = [
        1 => '07:30', 2 => '08:10', 3 => '08:50', 4 => '09:30',
        5 => '10:30', 6 => '11:10', 7 => '11:50', 8 => '13:30',
    ];

    /**
     * Build a timetable for every class in the current year. Existing slots
     * for the term are replaced. Returns lessons that could not be placed.
     */
    public static function generate(Term $term): array
    {
        TimetableSlot::where('term_id', $term->id)->delete();

        $classes = SchoolClass::where('academic_year_id', $term->academic_year_id)->pluck('id');
        $lessons = ClassSubject::with(['subject', 'schoolClass.level', 'teacher'])
            ->whereIn('school_class_id', $classes)->get()
            ->sortByDesc('periods_per_week');

        $classBusy = [];
        $teacherBusy = [];
        $unplaced = [];

        foreach ($lessons as $cs) {
            $needed = $cs->periods_per_week;
            $days = [1, 2, 3, 4, 5];
            $cursor = ($cs->id * 3) % 5;
            for ($round = 0; $round < 3 && $needed > 0; $round++) {
                foreach (range(0, 4) as $k) {
                    if ($needed === 0) {
                        break;
                    }
                    $day = $days[($cursor + $k) % 5];
                    $countToday = collect($classBusy[$cs->school_class_id][$day] ?? [])->filter(fn ($id) => $id === $cs->id)->count();
                    if ($countToday > $round) {
                        continue;
                    }
                    foreach (range(1, self::PERIODS) as $p) {
                        $period = (($p + $cs->id) % self::PERIODS) + 1;
                        if (isset($classBusy[$cs->school_class_id][$day][$period])) {
                            continue;
                        }
                        if ($cs->teacher_id && isset($teacherBusy[$cs->teacher_id][$day][$period])) {
                            continue;
                        }
                        TimetableSlot::create([
                            'school_id' => $cs->school_id,
                            'term_id' => $term->id,
                            'class_subject_id' => $cs->id,
                            'day' => $day,
                            'period' => $period,
                        ]);
                        $classBusy[$cs->school_class_id][$day][$period] = $cs->id;
                        if ($cs->teacher_id) {
                            $teacherBusy[$cs->teacher_id][$day][$period] = $cs->id;
                        }
                        $needed--;
                        break;
                    }
                }
            }
            if ($needed > 0) {
                $unplaced[] = $cs->schoolClass->name().' '.$cs->subject->name.': '.$needed.' period(s) not placed';
            }
        }

        return $unplaced;
    }

    /**
     * Teacher or class clashes in the stored slots.
     */
    public static function conflicts(Term $term): array
    {
        $slots = TimetableSlot::with(['classSubject.teacher', 'classSubject.subject', 'classSubject.schoolClass.level'])
            ->where('term_id', $term->id)->get();
        $out = [];

        foreach ($slots->groupBy(fn ($s) => $s->classSubject->teacher_id.'-'.$s->day.'-'.$s->period) as $group) {
            $first = $group->first();
            if ($group->count() > 1 && $first->classSubject->teacher_id) {
                $out[] = $first->classSubject->teacher->name.' is booked for '.$group->count().' classes on '
                    .TimetableSlot::DAYS[$first->day].', period '.$first->period.' ('
                    .$group->map(fn ($s) => $s->classSubject->schoolClass->name())->implode(', ').')';
            }
        }
        foreach ($slots->groupBy(fn ($s) => $s->classSubject->school_class_id.'-'.$s->day.'-'.$s->period) as $group) {
            if ($group->count() > 1) {
                $first = $group->first();
                $out[] = $first->classSubject->schoolClass->name().' has '.$group->count().' lessons on '
                    .TimetableSlot::DAYS[$first->day].', period '.$first->period;
            }
        }

        return $out;
    }
}
