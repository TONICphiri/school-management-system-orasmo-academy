<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Committee;
use App\Models\Department;
use App\Models\Level;
use App\Models\Section;
use App\Models\User;
use App\Services\Audit;
use App\Services\Notifier;
use Illuminate\Http\Request;

class StructureController extends Controller
{
    public function index()
    {
        $school = current_school();

        return view('school.structure', [
            'school' => $school,
            'levels' => Level::orderBy('phase')->orderBy('ordinal')->get(),
            'sections' => Section::with('head')->orderBy('from_ordinal')->get(),
            'departments' => Department::with(['head', 'subjects'])->orderBy('name')->get(),
            'committees' => Committee::with('members')->orderBy('name')->get(),
            'staff' => User::where('school_id', $school->id)->whereIn('role', User::TEACHING_ROLES)->orderBy('name')->get(),
        ]);
    }

    public function updateLevels(Request $request)
    {
        $data = $request->validate([
            'levels' => 'array',
            'levels.*.name' => 'required|string|max:40',
            'levels.*.national_exam' => 'nullable|in:PSLCE,JCE,MSCE',
            'levels.*.instruction_language' => 'required|in:Chichewa,English,Local language',
            'new_name' => 'nullable|string|max:40',
            'new_phase' => 'nullable|in:PRIMARY,SECONDARY',
        ]);
        foreach ($data['levels'] ?? [] as $id => $row) {
            Level::whereKey($id)->update($row);
        }
        if (! empty($data['new_name']) && ! empty($data['new_phase'])) {
            $next = (int) Level::where('phase', $data['new_phase'])->max('ordinal') + 1;
            Level::create(['phase' => $data['new_phase'], 'name' => $data['new_name'], 'ordinal' => $next,
                'instruction_language' => 'English']);
        }
        Audit::log('structure.levels', 'Updated class levels and examination points', current_school());

        return back()->with('status', 'Levels saved.');
    }

    public function updateSections(Request $request)
    {
        $data = $request->validate(['heads' => 'array', 'heads.*' => 'nullable|integer']);
        foreach ($data['heads'] ?? [] as $sectionId => $userId) {
            $section = Section::findOrFail($sectionId);
            $head = $userId ? $this->staffMember($userId) : null;
            if ($section->head_id !== $head?->id) {
                $section->update(['head_id' => $head?->id]);
                if ($head) {
                    Notifier::send($head, 'ASSIGNMENT', 'You are Section Head of the '.$section->name.' section',
                        'You now oversee classes in the '.$section->name.' section.', route('school.structure.index'));
                }
            }
        }
        Audit::log('structure.sections', 'Updated section heads', current_school());

        return back()->with('status', 'Section heads saved.');
    }

    public function storeDepartment(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:60']);
        $dept = Department::create($data);
        Audit::log('structure.department', 'Added department '.$dept->name, $dept);

        return back()->with('status', 'Department added.');
    }

    public function updateDepartments(Request $request)
    {
        $data = $request->validate(['heads' => 'array', 'heads.*' => 'nullable|integer']);
        foreach ($data['heads'] ?? [] as $deptId => $userId) {
            $dept = Department::findOrFail($deptId);
            $head = $userId ? $this->staffMember($userId) : null;
            if ($head && $head->role !== 'HEAD_OF_DEPARTMENT') {
                return back()->withErrors(['heads' => $head->name.' must hold the Head of Department role before leading '.$dept->name.'.']);
            }
            if ($dept->head_id !== $head?->id) {
                $dept->update(['head_id' => $head?->id]);
                if ($head) {
                    Notifier::send($head, 'ASSIGNMENT', 'You are Head of the '.$dept->name.' department',
                        'You will validate marks for '.$dept->subjects()->pluck('name')->implode(', ').'.', route('school.results.index'));
                }
            }
        }
        Audit::log('structure.departments', 'Updated heads of department', current_school());

        return back()->with('status', 'Heads of department saved.');
    }

    public function storeCommittee(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:60']);
        $c = Committee::create($data);
        Audit::log('structure.committee', 'Added committee '.$c->name, $c);

        return back()->with('status', 'Committee added.');
    }

    protected function staffMember($id): User
    {
        return User::where('school_id', current_school()->id)->whereIn('role', User::TEACHING_ROLES)->findOrFail($id);
    }
}
