<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\TimetableSlot;
use App\Models\User;
use App\Services\Audit;
use App\Services\Notifier;
use App\Services\Timetable;
use Illuminate\Http\Request;

class TimetableController extends Controller
{
    public function index(Request $request)
    {
        $school = current_school();
        $term = current_term();
        $year = $school->currentYear();
        $user = $request->user();

        $classes = $year ? SchoolClass::with('level')->where('academic_year_id', $year->id)->get()
            ->sortBy(fn ($c) => [$c->level->phase, $c->level->ordinal, $c->stream])->values() : collect();
        $teachers = User::where('school_id', $school->id)->whereIn('role', User::TEACHING_ROLES)->orderBy('name')->get();

        $view = $request->query('view', 'teacher');
        $selectedTeacher = $view === 'teacher' ? ($teachers->firstWhere('id', (int) $request->query('teacher_id', $user->id)) ?? $teachers->first()) : null;
        $selectedClass = $view === 'class' ? ($classes->firstWhere('id', (int) $request->query('class_id')) ?? $classes->first()) : null;

        $grid = [];
        if ($term) {
            $q = TimetableSlot::with(['classSubject.subject', 'classSubject.teacher', 'classSubject.schoolClass.level'])->where('term_id', $term->id);
            if ($selectedTeacher) {
                $q->whereHas('classSubject', fn ($w) => $w->where('teacher_id', $selectedTeacher->id));
            } elseif ($selectedClass) {
                $q->whereHas('classSubject', fn ($w) => $w->where('school_class_id', $selectedClass->id));
            }
            foreach ($q->get() as $slot) {
                $grid[$slot->day][$slot->period][] = $slot;
            }
        }

        return view('school.timetable', [
            'term' => $term,
            'grid' => $grid,
            'classes' => $classes,
            'teachers' => $teachers,
            'view' => $view,
            'selectedTeacher' => $selectedTeacher,
            'selectedClass' => $selectedClass,
            'conflicts' => $term ? Timetable::conflicts($term) : [],
            'slotCount' => $term ? TimetableSlot::where('term_id', $term->id)->count() : 0,
            'canManage' => $user->role === 'FACILITY_ADMIN',
        ]);
    }

    public function generate()
    {
        $term = current_term();
        abort_unless($term, 422, 'Set the current term first.');
        $unplaced = Timetable::generate($term);
        $conflicts = Timetable::conflicts($term);
        Audit::log('timetable.generated', 'Generated the master timetable for '.$term->label().'. '.count($unplaced).' lessons could not be placed.', $term);

        $staff = User::where('school_id', $term->school_id)->whereIn('role', User::TEACHING_ROLES)->where('status', 'ACTIVE')->get();
        Notifier::send($staff, 'CALENDAR', 'New timetable for '.$term->label(), 'The master timetable has been published. Check your periods for the week.', route('school.timetable.index'));

        $message = 'Timetable generated for '.$term->label().'.';
        if ($unplaced) {
            $message .= ' '.count($unplaced).' lesson groups need manual attention.';
        }

        return back()->with('status', $message)->with('unplaced', $unplaced)->with('conflictCount', count($conflicts));
    }
}
