<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Subject;
use App\Services\Audit;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        return view('school.subjects', [
            'subjects' => Subject::with('department')->withCount('classSubjects')->orderBy('phase')->orderByDesc('is_core')->orderBy('name')->get()->groupBy('phase'),
            'departments' => Department::orderBy('name')->get(),
            'school' => current_school(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|integer',
            'name' => 'required|string|max:60',
            'code' => 'required|string|max:12',
            'phase' => 'required|in:PRIMARY,SECONDARY',
            'department_id' => 'nullable|integer',
            'is_core' => 'nullable|boolean',
            'ca_weight' => 'required|integer|min:0|max:100',
        ]);
        $data['exam_weight'] = 100 - $data['ca_weight'];
        $data['is_core'] = $request->boolean('is_core');
        $data['is_optional'] = $data['phase'] === 'PRIMARY' && ! $data['is_core'];
        if ($data['department_id']) {
            Department::findOrFail($data['department_id']);
        }

        if ($id = $data['id'] ?? null) {
            $subject = Subject::findOrFail($id);
            $subject->update(collect($data)->except('id')->all());
            Audit::log('subject.updated', 'Updated '.$subject->name.' (CA '.$subject->ca_weight.'%, exam '.$subject->exam_weight.'%)', $subject);
        } else {
            $subject = Subject::create(collect($data)->except('id')->all());
            Audit::log('subject.created', 'Added subject '.$subject->name, $subject);
        }

        return back()->with('status', $subject->name.' saved.');
    }
}
