@php
    $u = auth()->user();
    $link = function ($route, $label, $icon, $pattern = null, $params = [], $count = null) {
        $active = request()->routeIs($pattern ?? $route);
        return '<a href="'.route($route, $params).'" class="nav-link'.($active ? ' active' : '').'">'.icon($icon).'<span>'.e($label).'</span>'.($count ? '<span class="nav-count">'.$count.'</span>' : '').'</a>';
    };
    $leader = $u->isSchoolLeader();
    $admin = $u->isSchoolAdmin();
@endphp
<nav class="flex-1 pt-2 pb-4">
    {!! $link('dashboard', 'Overview', 'home') !!}

    @if ($u->isSystemAdmin())
        <div class="nav-group">National administration</div>
        {!! $link('admin.schools.index', 'Schools', 'school', 'admin.schools.*') !!}
        {!! $link('admin.supervisors.index', 'Supervisors', 'map', 'admin.supervisors.*') !!}
        {!! $link('admin.inspections', 'Inspection reports', 'clipboard') !!}
        <div class="nav-group">Security</div>
        {!! $link('admin.audit', 'Audit log', 'shield') !!}
        {!! $link('admin.outbox', 'SMS and email outbox', 'send') !!}
    @elseif ($u->isSupervisor())
        <div class="nav-group">Supervision</div>
        {!! $link('supervisor.inspections.index', 'Inspection reports', 'clipboard', 'supervisor.inspections.*') !!}
        {!! $link('supervisor.escalations', 'Escalated concerns', 'flag') !!}
    @elseif ($u->isSchoolStaff())
        @if ($u->isTeachingStaff())
            <div class="nav-group">Teaching</div>
            {!! $link('school.marks.index', 'Marks entry', 'edit', 'school.marks.*') !!}
            {!! $link('school.results.index', 'Results and approval', 'check-square', 'school.results.*') !!}
        @endif
        <div class="nav-group">School</div>
        {!! $link('school.timetable.index', 'Timetable', 'clock', 'school.timetable.*') !!}
        {!! $link('school.classes.index', 'Classes', 'grid', 'school.classes.*') !!}
        @if ($leader || $u->ownedClassIds()->isNotEmpty() || $u->taughtClassIds()->isNotEmpty())
            {!! $link('school.students.index', 'Learners', 'users', 'school.students.*') !!}
        @endif
        {!! $link('school.staff.index', 'Staff', 'user', 'school.staff.*') !!}
        {!! $link('school.subjects.index', 'Subjects', 'book', 'school.subjects.*') !!}
        {!! $link('school.calendar.index', 'Academic calendar', 'calendar', 'school.calendar.*') !!}
        {!! $link('school.structure.index', 'School structure', 'layers', 'school.structure.*') !!}
        @if ($leader)
            <div class="nav-group">National examinations</div>
            {!! $link('school.maneb.index', 'MANEB candidates', 'award', 'school.maneb.*') !!}
        @endif
        @if ($admin || $u->hasRole('DEPUTY_HEAD', 'DEPUTY_HEAD_ADMIN'))
            <div class="nav-group">Finance</div>
            {!! $link('school.finance.index', 'Income and expenditure', 'wallet', 'school.finance.*') !!}
        @endif
        @if ($admin)
            <div class="nav-group">Governance and oversight</div>
            {!! $link('school.governance.index', 'SMC, PTA and Board', 'users', 'school.governance.index') !!}
            {!! $link('school.feedback.index', 'Concerns', 'message', 'school.feedback.*') !!}
            {!! $link('school.audit', 'Audit log', 'shield') !!}
        @endif
    @elseif ($u->role === 'GOVERNANCE')
        <div class="nav-group">Governance</div>
        {!! $link('school.feedback.index', 'Concerns', 'message', 'school.feedback.*') !!}
        {!! $link('school.finance.index', 'Financial summary', 'wallet', 'school.finance.*') !!}
        @if ($u->governance?->body === 'BOG')
            {!! $link('school.governance.audit', 'Audit log', 'shield') !!}
        @endif
    @endif

    <div class="nav-group">Account</div>
    {!! $link('notices.index', 'Notifications', 'bell', 'notices.*', [], $u->unreadNoticeCount() ?: null) !!}
    {!! $link('account.edit', 'My account', 'settings', 'account.*') !!}
</nav>
