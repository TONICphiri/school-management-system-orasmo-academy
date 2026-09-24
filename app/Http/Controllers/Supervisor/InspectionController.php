<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\InspectionReport;
use App\Models\School;
use App\Services\Audit;
use App\Services\Notifier;
use App\Services\Stats;
use Illuminate\Http\Request;

class InspectionController extends Controller
{
    public const RATINGS = ['VERY_GOOD' => 'Very good', 'GOOD' => 'Good', 'FAIR' => 'Fair', 'POOR' => 'Poor'];

    public function index(Request $request)
    {
        $ids = Stats::jurisdiction($request->user())->pluck('id');

        return view('supervisor.inspections', [
            'reports' => InspectionReport::with(['school', 'supervisor'])->whereIn('school_id', $ids)->latest('visit_date')->paginate(20),
            'ratings' => self::RATINGS,
        ]);
    }

    public function create(Request $request, School $school)
    {
        abort_unless(Stats::canSupervise($request->user(), $school), 403);

        return view('supervisor.inspection-form', ['school' => $school, 'stats' => Stats::school($school), 'ratings' => self::RATINGS]);
    }

    public function store(Request $request, School $school)
    {
        abort_unless(Stats::canSupervise($request->user(), $school), 403);
        $data = $request->validate([
            'visit_date' => 'required|date|before_or_equal:today',
            'overall_rating' => 'required|in:'.implode(',', array_keys(self::RATINGS)),
            'findings' => 'required|string|max:5000',
            'recommendations' => 'required|string|max:5000',
            'flag_follow_up' => 'nullable|boolean',
            'follow_up_by' => 'nullable|required_if:flag_follow_up,1|date|after:visit_date',
        ]);

        $report = InspectionReport::create($data + [
            'school_id' => $school->id,
            'supervisor_id' => $request->user()->id,
            'directorate' => $school->directorate(),
            'flag_follow_up' => $request->boolean('flag_follow_up'),
            'status' => 'SUBMITTED',
        ]);
        Audit::log('inspection.submitted', $request->user()->roleLabel().' submitted an inspection report for '.$school->name.' rated '.self::RATINGS[$report->overall_rating], $report, $school->id);

        $priority = $report->flag_follow_up ? 'HIGH' : 'NORMAL';
        Notifier::send(Notifier::systemAdmins(), 'INSPECTION', 'Inspection report for '.$school->name,
            'Routed to the '.$report->directorate.'. Rating: '.self::RATINGS[$report->overall_rating].($report->flag_follow_up ? '. Flagged for follow-up by '.$report->follow_up_by->format('j M Y') : '').'.',
            route('admin.inspections'), $priority);
        Notifier::send(Notifier::schoolRoles($school->id, \App\Models\User::ADMIN_ROLES), 'INSPECTION', 'Inspection report received',
            $request->user()->name.' ('.$request->user()->roleLabel().') submitted the report of the visit on '.$report->visit_date->format('j M Y').'.', null, $priority, ['APP', 'PREFERRED']);

        return redirect()->route('supervisor.inspections.show', $report)->with('status', 'Inspection report submitted to the '.$report->directorate.'.');
    }

    public function show(Request $request, InspectionReport $report)
    {
        abort_unless(Stats::canSupervise($request->user(), $report->school), 403);

        return view('supervisor.inspection-show', ['report' => $report->load(['school.district', 'supervisor']), 'ratings' => self::RATINGS]);
    }
}
