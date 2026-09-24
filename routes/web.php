<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\School;
use App\Http\Controllers\Supervisor;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/activate', [AuthController::class, 'showActivate'])->name('activate');
    Route::post('/activate', [AuthController::class, 'activate'])->middleware('throttle:10,1');
    Route::post('/activate/resend', [AuthController::class, 'resendActivation'])->name('activate.resend')->middleware('throttle:3,10');
});

Route::middleware('auth')->group(function () {
    Route::get('/verify', [AuthController::class, 'showMfa'])->name('mfa.show');
    Route::post('/verify', [AuthController::class, 'verifyMfa'])->name('mfa.verify')->middleware('throttle:10,1');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
});

Route::middleware(['auth', 'ready'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/notifications', [NoticeController::class, 'index'])->name('notices.index');
    Route::get('/notifications/{notice}', [NoticeController::class, 'open'])->name('notices.open');
    Route::post('/notifications/read-all', [NoticeController::class, 'readAll'])->name('notices.readAll');

    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::post('/account/password', [AccountController::class, 'password'])->name('account.password');
    Route::post('/account/security', [AccountController::class, 'security'])->name('account.security');

    // National administration
    Route::prefix('admin')->name('admin.')->middleware('role:SYSTEM_ADMIN')->group(function () {
        Route::get('/schools', [Admin\SchoolController::class, 'index'])->name('schools.index');
        Route::get('/schools/create', [Admin\SchoolController::class, 'create'])->name('schools.create');
        Route::post('/schools', [Admin\SchoolController::class, 'store'])->name('schools.store');
        Route::get('/schools/{school}', [Admin\SchoolController::class, 'show'])->name('schools.show');
        Route::get('/schools/{school}/edit', [Admin\SchoolController::class, 'edit'])->name('schools.edit');
        Route::put('/schools/{school}', [Admin\SchoolController::class, 'update'])->name('schools.update');
        Route::post('/schools/{school}/status', [Admin\SchoolController::class, 'status'])->name('schools.status');
        Route::post('/schools/{school}/resend', [Admin\SchoolController::class, 'resend'])->name('schools.resend');
        Route::get('/supervisors', [Admin\SupervisorController::class, 'index'])->name('supervisors.index');
        Route::post('/supervisors', [Admin\SupervisorController::class, 'store'])->name('supervisors.store');
        Route::get('/audit', [Admin\AuditController::class, 'index'])->name('audit');
        Route::get('/outbox', [Admin\AuditController::class, 'outbox'])->name('outbox');
        Route::get('/inspections', [Admin\AuditController::class, 'inspections'])->name('inspections');
    });

    // School tenant
    Route::prefix('school')->name('school.')->group(function () {
        $leaders = 'role:FACILITY_ADMIN,DEPUTY_HEAD,DEPUTY_HEAD_ACADEMIC,DEPUTY_HEAD_ADMIN';
        $staff = 'role:FACILITY_ADMIN,DEPUTY_HEAD,DEPUTY_HEAD_ACADEMIC,DEPUTY_HEAD_ADMIN,SECTION_HEAD,HEAD_OF_DEPARTMENT,CLASS_TEACHER,FORM_MASTER,SUBJECT_TEACHER';

        Route::middleware('role:FACILITY_ADMIN')->group(function () {
            Route::post('/calendar/years', [School\CalendarController::class, 'storeYear'])->name('calendar.years');
            Route::post('/calendar/terms', [School\CalendarController::class, 'storeTerm'])->name('calendar.terms');
            Route::post('/calendar/terms/{term}/current', [School\CalendarController::class, 'makeCurrent'])->name('calendar.current');
            Route::post('/calendar/breaks', [School\CalendarController::class, 'storeBreak'])->name('calendar.breaks');

            Route::post('/structure/levels', [School\StructureController::class, 'updateLevels'])->name('structure.levels');
            Route::post('/structure/sections', [School\StructureController::class, 'updateSections'])->name('structure.sections');
            Route::post('/structure/departments', [School\StructureController::class, 'storeDepartment'])->name('structure.departments');
            Route::post('/structure/departments/heads', [School\StructureController::class, 'updateDepartments'])->name('structure.departments.heads');
            Route::post('/structure/committees', [School\StructureController::class, 'storeCommittee'])->name('structure.committees');

            Route::get('/staff/create', [School\StaffController::class, 'create'])->name('staff.create');
            Route::post('/staff', [School\StaffController::class, 'store'])->name('staff.store');
            Route::get('/staff/{user}/edit', [School\StaffController::class, 'edit'])->name('staff.edit');
            Route::put('/staff/{user}', [School\StaffController::class, 'update'])->name('staff.update');
            Route::post('/staff/{user}/resend', [School\StaffController::class, 'resend'])->name('staff.resend');
            Route::post('/staff/{user}/status', [School\StaffController::class, 'status'])->name('staff.status');

            Route::get('/governance', [School\GovernanceController::class, 'index'])->name('governance.index');
            Route::post('/governance', [School\GovernanceController::class, 'store'])->name('governance.store');
            Route::post('/governance/summary', [School\GovernanceController::class, 'sendSummary'])->name('governance.summary');

            Route::post('/classes', [School\ClassController::class, 'store'])->name('classes.store');
            Route::put('/classes/{class}', [School\ClassController::class, 'update'])->name('classes.update');
            Route::post('/classes/{class}/subjects', [School\ClassController::class, 'assign'])->name('classes.assign');
            Route::post('/subjects', [School\SubjectController::class, 'store'])->name('subjects.store');

            Route::post('/timetable/generate', [School\TimetableController::class, 'generate'])->name('timetable.generate');
            Route::post('/results/{class}/release', [School\ResultController::class, 'release'])->name('results.release');
            Route::get('/audit', [School\FeedbackController::class, 'audit'])->name('audit');
        });

        Route::middleware($leaders)->group(function () {
            Route::get('/students/create', [School\StudentController::class, 'create'])->name('students.create');
            Route::post('/students', [School\StudentController::class, 'store'])->name('students.store');
            Route::get('/students/{student}/edit', [School\StudentController::class, 'edit'])->name('students.edit');
            Route::put('/students/{student}', [School\StudentController::class, 'update'])->name('students.update');
            Route::post('/students/{student}/electives', [School\StudentController::class, 'electives'])->name('students.electives');
            Route::get('/maneb', [School\ManebController::class, 'index'])->name('maneb.index');
            Route::post('/maneb/export', [School\ManebController::class, 'export'])->name('maneb.export');
            Route::post('/maneb/numbers', [School\ManebController::class, 'numbers'])->name('maneb.numbers');
        });

        Route::middleware('role:DEPUTY_HEAD_ACADEMIC,DEPUTY_HEAD,FACILITY_ADMIN')->group(function () {
            Route::post('/results/{class}/approve', [School\ResultController::class, 'approve'])->name('results.approve');
        });
        Route::middleware('role:HEAD_OF_DEPARTMENT,SECTION_HEAD,FACILITY_ADMIN,DEPUTY_HEAD_ACADEMIC')->group(function () {
            Route::post('/results/subject/{status}/validate', [School\ResultController::class, 'validateSubject'])->name('results.validate');
            Route::post('/results/subject/{status}/return', [School\ResultController::class, 'returnSubject'])->name('results.return');
        });

        Route::middleware($staff)->group(function () {
            Route::get('/calendar', [School\CalendarController::class, 'index'])->name('calendar.index');
            Route::get('/structure', [School\StructureController::class, 'index'])->name('structure.index');
            Route::get('/staff', [School\StaffController::class, 'index'])->name('staff.index');
            Route::get('/classes', [School\ClassController::class, 'index'])->name('classes.index');
            Route::get('/classes/{class}', [School\ClassController::class, 'show'])->name('classes.show');
            Route::get('/subjects', [School\SubjectController::class, 'index'])->name('subjects.index');
            Route::get('/students', [School\StudentController::class, 'index'])->name('students.index');
            Route::get('/timetable', [School\TimetableController::class, 'index'])->name('timetable.index');

            Route::get('/attendance/{class}', [School\AttendanceController::class, 'edit'])->name('attendance.edit');
            Route::post('/attendance/{class}', [School\AttendanceController::class, 'store'])->name('attendance.store');

            Route::get('/marks', [School\MarkController::class, 'index'])->name('marks.index');
            Route::get('/marks/{lesson}', [School\MarkController::class, 'show'])->name('marks.show');
            Route::post('/marks/{lesson}/assessments', [School\MarkController::class, 'storeAssessment'])->name('marks.assessments');
            Route::get('/marks/assessment/{assessment}', [School\MarkController::class, 'sheet'])->name('marks.sheet');
            Route::post('/marks/assessment/{assessment}', [School\MarkController::class, 'saveSheet'])->name('marks.save');
            Route::post('/marks/{lesson}/submit', [School\MarkController::class, 'submit'])->name('marks.submit');

            Route::get('/results', [School\ResultController::class, 'index'])->name('results.index');
            Route::get('/results/{class}', [School\ResultController::class, 'show'])->name('results.show');
            Route::post('/results/{class}/review', [School\ResultController::class, 'review'])->name('results.review');
            Route::post('/results/{class}/comments', [School\ResultController::class, 'comments'])->name('results.comments');
        });

        Route::get('/students/{student}', [School\StudentController::class, 'show'])->name('students.show');
        Route::get('/reports/{student}', [School\ReportController::class, 'card'])->name('reports.card');

        Route::middleware('role:GOVERNANCE,FACILITY_ADMIN')->group(function () {
            Route::get('/feedback', [School\FeedbackController::class, 'index'])->name('feedback.index');
        });
        Route::middleware('role:GOVERNANCE')->group(function () {
            Route::post('/feedback', [School\FeedbackController::class, 'store'])->name('feedback.store');
            Route::post('/feedback/{item}/escalate', [School\FeedbackController::class, 'escalate'])->name('feedback.escalate');
            Route::get('/governance/audit', [School\FeedbackController::class, 'audit'])->name('governance.audit');
        });
        Route::post('/feedback/{item}/respond', [School\FeedbackController::class, 'respond'])->name('feedback.respond')->middleware('role:FACILITY_ADMIN');
    });

    // External supervision
    Route::prefix('supervision')->name('supervisor.')->middleware('role:EDM,DEM,PEA')->group(function () {
        Route::get('/schools/{school}', [Supervisor\SupervisionController::class, 'school'])->name('school');
        Route::get('/inspections', [Supervisor\InspectionController::class, 'index'])->name('inspections.index');
        Route::get('/inspections/create/{school}', [Supervisor\InspectionController::class, 'create'])->name('inspections.create');
        Route::post('/inspections/{school}', [Supervisor\InspectionController::class, 'store'])->name('inspections.store');
        Route::get('/inspections/{report}', [Supervisor\InspectionController::class, 'show'])->name('inspections.show');
        Route::get('/escalations', [Supervisor\SupervisionController::class, 'escalations'])->name('escalations');
        Route::post('/escalations/{item}/close', [Supervisor\SupervisionController::class, 'closeEscalation'])->name('escalations.close');
    });
});
