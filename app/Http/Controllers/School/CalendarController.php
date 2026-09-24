<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\TermBreak;
use App\Models\User;
use App\Services\Audit;
use App\Services\Notifier;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index()
    {
        return view('school.calendar', [
            'years' => AcademicYear::with(['terms.breaks'])->orderByDesc('starts_on')->get(),
        ]);
    }

    public function storeYear(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:20',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after:starts_on',
        ]);
        $year = AcademicYear::create($data + ['is_current' => false]);
        foreach ([1, 2, 3] as $n) {
            $start = $year->starts_on->copy()->addMonths(($n - 1) * 4);
            Term::create([
                'academic_year_id' => $year->id, 'number' => $n,
                'starts_on' => $start, 'ends_on' => $start->copy()->addWeeks(13),
            ]);
        }
        Audit::log('calendar.year', 'Added academic year '.$year->name, $year);

        return back()->with('status', 'Academic year '.$year->name.' added with three terms. Adjust the term dates below.');
    }

    public function storeTerm(Request $request)
    {
        $data = $request->validate([
            'term_id' => 'required|integer',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after:starts_on',
        ]);
        $term = Term::findOrFail($data['term_id']);
        $term->update(['starts_on' => $data['starts_on'], 'ends_on' => $data['ends_on']]);
        Audit::log('calendar.term', 'Updated dates of '.$term->label(), $term);

        return back()->with('status', 'Term dates saved.');
    }

    public function makeCurrent(Term $term)
    {
        Term::query()->update(['is_current' => false]);
        AcademicYear::query()->update(['is_current' => false]);
        $term->update(['is_current' => true]);
        $term->academicYear->update(['is_current' => true]);
        Audit::log('calendar.current', 'Set '.$term->label().' as the current term', $term);

        $staff = User::where('school_id', $term->school_id)->whereIn('role', User::TEACHING_ROLES)->where('status', 'ACTIVE')->get();
        Notifier::send($staff, 'CALENDAR', $term->label().' is now open',
            'Mark entry and attendance now record against '.$term->label().' ('.$term->starts_on->format('j M').' to '.$term->ends_on->format('j M Y').').',
            route('school.calendar.index'));

        return back()->with('status', $term->label().' is now the current term.');
    }

    public function storeBreak(Request $request)
    {
        $data = $request->validate([
            'term_id' => 'required|integer',
            'name' => 'required|string|max:60',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after_or_equal:starts_on',
        ]);
        $term = Term::findOrFail($data['term_id']);
        $break = TermBreak::create($data);
        Audit::log('calendar.break', 'Added '.$break->name.' to '.$term->label(), $break);

        $people = User::where('school_id', $term->school_id)->where('status', 'ACTIVE')->whereNotIn('role', ['STUDENT'])->get();
        Notifier::send($people, 'CALENDAR', $break->name.' added to the calendar',
            'School closes from '.$break->starts_on->format('l j F').' to '.$break->ends_on->format('l j F Y').'.', route('school.calendar.index'));

        return back()->with('status', 'Break added and staff and parents notified.');
    }
}
