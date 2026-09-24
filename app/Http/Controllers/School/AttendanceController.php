<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Services\Audit;
use App\Services\Notifier;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    protected function authoriseClass(Request $request, SchoolClass $class): void
    {
        $user = $request->user();
        abort_unless($user->isSchoolLeader() || $class->class_teacher_id === $user->id, 403, 'Only the class teacher or school leaders can take the register for this class.');
    }

    public function edit(Request $request, SchoolClass $class)
    {
        $this->authoriseClass($request, $class);
        $date = $request->query('date', now()->toDateString());
        $students = $class->students()->where('status', 'ENROLLED')->get();
        $existing = Attendance::where('school_class_id', $class->id)->where('attended_on', $date)->pluck('status', 'student_id');

        return view('school.attendance', ['class' => $class->load('level'), 'students' => $students, 'existing' => $existing, 'date' => $date]);
    }

    public function store(Request $request, SchoolClass $class)
    {
        $this->authoriseClass($request, $class);
        $data = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'status' => 'required|array',
            'status.*' => 'required|in:PRESENT,ABSENT,LATE,EXCUSED',
        ]);
        $ids = $class->students()->pluck('id');
        $absent = [];
        foreach ($data['status'] as $studentId => $status) {
            if (! $ids->contains((int) $studentId)) {
                continue;
            }
            Attendance::updateOrCreate(
                ['student_id' => $studentId, 'attended_on' => $data['date']],
                ['school_class_id' => $class->id, 'status' => $status, 'recorded_by' => $request->user()->id, 'school_id' => $class->school_id]
            );
            if ($status === 'ABSENT') {
                $absent[] = (int) $studentId;
            }
        }
        Audit::log('attendance.recorded', 'Recorded attendance for '.$class->name().' on '.$data['date'].' ('.count($absent).' absent)', $class);

        if ($request->boolean('notify_parents') && $absent) {
            foreach ($class->students()->with('guardians')->whereIn('id', $absent)->get() as $student) {
                Notifier::send($student->guardians, 'GENERAL', $student->first_name.' was absent today',
                    $student->fullName().' was marked absent from '.$class->name().' on '.\Carbon\Carbon::parse($data['date'])->format('l j F').'. Please contact the class teacher if this is not expected.',
                    route('dashboard'), 'NORMAL', ['APP', 'SMS']);
            }
        }

        return redirect()->route('school.attendance.edit', [$class, 'date' => $data['date']])->with('status', 'Register saved for '.count($data['status']).' learners.');
    }
}
