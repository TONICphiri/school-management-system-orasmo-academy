<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Services\Audit;
use App\Services\Grading;
use Illuminate\Http\Request;

class ManebController extends Controller
{
    protected function candidates(?string $exam)
    {
        $year = current_school()->currentYear();
        $levels = Level::whereNotNull('national_exam')->when($exam, fn ($q) => $q->where('national_exam', $exam))->pluck('id');
        $classes = $year ? SchoolClass::with(['level', 'classSubjects.subject'])->where('academic_year_id', $year->id)->whereIn('level_id', $levels)->get() : collect();
        $term = current_term();
        $rows = collect();

        foreach ($classes as $class) {
            $results = $term ? Grading::classResults($class, $term) : ['learners' => []];
            foreach ($class->students()->with('electives.subject')->where('status', 'ENROLLED')->get() as $student) {
                $subjects = $class->classSubjects->filter(fn ($cs) => $cs->subject->is_core || $student->electives->contains('id', $cs->id))
                    ->map(fn ($cs) => $cs->subject)->sortBy('name');
                $r = $results['learners'][$student->id] ?? [];
                $rows->push([
                    'student' => $student,
                    'class' => $class,
                    'exam' => $class->level->national_exam,
                    'subjects' => $subjects,
                    'eligible' => $r['exam_eligible'] ?? (($r['average'] ?? 0) >= 40),
                    'note' => $r['exam_note'] ?? (isset($r['average']) ? 'Average '.num($r['average']) : 'No marks yet'),
                ]);
            }
        }

        return $rows->sortBy(fn ($r) => [$r['exam'], $r['student']->last_name, $r['student']->first_name])->values();
    }

    public function index(Request $request)
    {
        $exam = $request->query('exam');

        return view('school.maneb', [
            'rows' => $this->candidates($exam),
            'exam' => $exam,
            'exams' => Level::whereNotNull('national_exam')->distinct()->pluck('national_exam'),
            'school' => current_school(),
            'zipEncryption' => class_exists(\ZipArchive::class) && method_exists(\ZipArchive::class, 'setEncryptionName'),
        ]);
    }

    public function numbers(Request $request)
    {
        $data = $request->validate([
            'centre_number' => 'nullable|string|max:20',
            'numbers' => 'array',
            'numbers.*' => 'nullable|string|max:30',
        ]);
        $school = current_school();
        if (array_key_exists('centre_number', $data)) {
            $school->update(['maneb_centre_number' => $data['centre_number']]);
        }
        $count = 0;
        foreach ($data['numbers'] ?? [] as $id => $number) {
            $count += Student::whereKey($id)->update(['maneb_exam_number' => $number ?: null]);
        }
        Audit::log('maneb.numbers', 'Updated MANEB centre number and '.$count.' examination numbers', $school, $school->id);

        return back()->with('status', 'Examination numbers saved.');
    }

    public function export(Request $request)
    {
        $data = $request->validate([
            'exam' => 'required|in:PSLCE,JCE,MSCE',
            'password' => 'required|string|min:8|max:64',
        ], ['password.required' => 'Set a password to protect the export file.']);
        abort_unless(class_exists(\ZipArchive::class), 500, 'The PHP zip extension is needed for encrypted exports. Enable extension=zip in php.ini.');

        $school = current_school();
        $rows = $this->candidates($data['exam']);
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, ['CentreNumber', 'CentreName', 'District', 'Examination', 'ExamNumber', 'AdmissionNumber', 'Surname', 'FirstName', 'Sex', 'DateOfBirth', 'Class', 'Subjects', 'SchoolRecommendation']);
        foreach ($rows as $r) {
            $s = $r['student'];
            fputcsv($csv, [
                $school->maneb_centre_number, $school->name, $school->district->name, $r['exam'], $s->maneb_exam_number,
                $s->admission_number, strtoupper($s->last_name), $s->first_name, $s->gender === 'Female' ? 'F' : 'M',
                $s->date_of_birth?->format('d/m/Y'), $r['class']->name(), $r['subjects']->pluck('code')->implode(' '),
                $r['eligible'] ? 'ELIGIBLE' : 'REVIEW',
            ]);
        }
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        $base = $data['exam'].'_'.($school->maneb_centre_number ?: $school->code).'_'.now()->format('Ymd_His');
        $zipPath = storage_path('app/'.$base.'.zip');
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString($base.'.csv', $content);
        $zip->setEncryptionName($base.'.csv', \ZipArchive::EM_AES_256, $data['password']);
        $zip->close();

        Audit::log('maneb.export', 'Exported encrypted '.$data['exam'].' candidate register with '.$rows->count().' candidates', $school, $school->id);

        return response()->download($zipPath, $base.'.zip')->deleteFileAfterSend(true);
    }
}
