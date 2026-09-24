<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\InspectionReport;
use App\Models\MessageLog;
use App\Models\School;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $q = AuditLog::with(['user', 'school']);
        if ($request->filled('school_id')) {
            $q->where('school_id', $request->query('school_id'));
        }
        if ($request->filled('action')) {
            $q->where('action', 'like', $request->query('action').'%');
        }

        return view('admin.audit', [
            'logs' => $q->latest('created_at')->paginate(30)->withQueryString(),
            'schools' => School::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function outbox()
    {
        return view('admin.outbox', ['messages' => MessageLog::latest()->paginate(30)]);
    }

    public function inspections()
    {
        return view('admin.inspections', [
            'reports' => InspectionReport::with(['school', 'supervisor'])->latest('visit_date')->paginate(20),
        ]);
    }
}
