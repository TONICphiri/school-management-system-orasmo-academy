<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassResultStatus;
use App\Models\ReportComment;
use App\Models\Student;
use App\Services\Audit;
use App\Services\Grading;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function card(Request $request, Student $student)
    {
        $user = $request->user();
        abort_unless($user->school_id === $student->school_id, 404);
        $isFamily = in_array($user->role, ['PARENT', 'STUDENT']);
        abort_unless($user->canSeeStudentPii($student), 403);

        $term = current_term();
        $student->load(['schoolClass.level', 'schoolClass.classTeacher', 'school']);
        $status = ClassResultStatus::where('school_class_id', $student->school_class_id)->where('term_id', $term->id)->first();
        if ($isFamily && (! $status || $status->stage !== 'RELEASED')) {
            return redirect()->route('dashboard')->withErrors(['report' => 'The '.$term->label().' report for '.$student->first_name.' has not been released yet.']);
        }

        $attendance = Attendance::where('student_id', $student->id)->whereBetween('attended_on', [$term->starts_on, $term->ends_on])
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        Audit::log('report.viewed', $user->name.' opened the report card of '.$student->fullName(), $student);

        return view('school.report-card', [
            'student' => $student,
            'term' => $term,
            'report' => Grading::studentReport($student, $term),
            'status' => $status,
            'comment' => ReportComment::where('student_id', $student->id)->where('term_id', $term->id)->first(),
            'attendance' => $attendance,
            'bands' => Grading::bands($student->schoolClass->level->phase),
            'nextTerm' => \App\Models\Term::where('starts_on', '>', $term->ends_on)->orderBy('starts_on')->first(),
        ]);
    }
}
