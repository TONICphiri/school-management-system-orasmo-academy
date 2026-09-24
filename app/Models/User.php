<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'mfa_enabled' => 'boolean',
            'policy_accepted_at' => 'datetime',
            'activated_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public const ROLES = [
        'SYSTEM_ADMIN' => 'System Administrator',
        'FACILITY_ADMIN' => 'Head Teacher',
        'SCHOOL_ADMIN' => 'School System Administrator',
        'DEPUTY_HEAD' => 'Deputy Head Teacher',
        'DEPUTY_HEAD_ACADEMIC' => 'Deputy Head (Academic)',
        'DEPUTY_HEAD_ADMIN' => 'Deputy Head (Administration)',
        'SECTION_HEAD' => 'Section Head',
        'HEAD_OF_DEPARTMENT' => 'Head of Department',
        'CLASS_TEACHER' => 'Class Teacher',
        'FORM_MASTER' => 'Form Master or Mistress',
        'SUBJECT_TEACHER' => 'Subject Teacher',
        'STUDENT' => 'Learner',
        'PARENT' => 'Parent or Guardian',
        'GOVERNANCE' => 'Governance Member',
        'EDM' => 'Education Division Manager',
        'DEM' => 'District Education Manager',
        'PEA' => 'Primary Education Advisor',
    ];

    public const PRIMARY_STAFF_ROLES = ['DEPUTY_HEAD', 'SECTION_HEAD', 'CLASS_TEACHER', 'SUBJECT_TEACHER'];
    public const SECONDARY_STAFF_ROLES = ['DEPUTY_HEAD_ACADEMIC', 'DEPUTY_HEAD_ADMIN', 'HEAD_OF_DEPARTMENT', 'FORM_MASTER', 'SUBJECT_TEACHER'];
    public const TEACHING_ROLES = ['FACILITY_ADMIN', 'DEPUTY_HEAD', 'DEPUTY_HEAD_ACADEMIC', 'DEPUTY_HEAD_ADMIN', 'SECTION_HEAD', 'HEAD_OF_DEPARTMENT', 'CLASS_TEACHER', 'FORM_MASTER', 'SUBJECT_TEACHER'];
    public const ADMIN_ROLES = ['FACILITY_ADMIN', 'SCHOOL_ADMIN'];
    public const STAFF_ROLES = ['FACILITY_ADMIN', 'SCHOOL_ADMIN', 'DEPUTY_HEAD', 'DEPUTY_HEAD_ACADEMIC', 'DEPUTY_HEAD_ADMIN', 'SECTION_HEAD', 'HEAD_OF_DEPARTMENT', 'CLASS_TEACHER', 'FORM_MASTER', 'SUBJECT_TEACHER'];
    public const DEPUTY_ROLES = ['DEPUTY_HEAD', 'DEPUTY_HEAD_ACADEMIC', 'DEPUTY_HEAD_ADMIN'];
    public const SUPERVISOR_ROLES = ['EDM', 'DEM', 'PEA'];

    public function school() { return $this->belongsTo(School::class); }
    public function division() { return $this->belongsTo(Division::class); }
    public function district() { return $this->belongsTo(District::class); }
    public function zone() { return $this->belongsTo(Zone::class); }
    public function teacherProfile() { return $this->hasOne(TeacherProfile::class); }
    public function committees() { return $this->belongsToMany(Committee::class)->withPivot('position'); }
    public function governance() { return $this->hasOne(GovernanceMembership::class); }
    public function children() { return $this->belongsToMany(Student::class, 'guardian_student')->withPivot('relationship'); }
    public function studentRecord() { return $this->hasOne(Student::class); }
    public function notices() { return $this->hasMany(Notice::class)->latest(); }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isSystemAdmin(): bool { return $this->role === 'SYSTEM_ADMIN'; }
    public function isSupervisor(): bool { return in_array($this->role, self::SUPERVISOR_ROLES, true); }
    public function isTeachingStaff(): bool { return in_array($this->role, self::TEACHING_ROLES, true); }
    public function isSchoolLeader(): bool { return in_array($this->role, self::ADMIN_ROLES, true) || in_array($this->role, self::DEPUTY_ROLES, true); }
    public function isSchoolAdmin(): bool { return in_array($this->role, self::ADMIN_ROLES, true); }
    public function isSchoolStaff(): bool { return in_array($this->role, self::STAFF_ROLES, true); }

    /** Classes this user owns (class teacher or form master) in the current year. */
    public function ownedClassIds()
    {
        $year = $this->school?->currentYear();

        return SchoolClass::where('class_teacher_id', $this->id)->when($year, fn ($q) => $q->where('academic_year_id', $year->id))->pluck('id');
    }

    /** Classes this user teaches at least one subject in. */
    public function taughtClassIds()
    {
        $year = $this->school?->currentYear();
        $ids = ClassSubject::where('teacher_id', $this->id)->distinct()->pluck('school_class_id');

        return $year ? SchoolClass::whereIn('id', $ids)->where('academic_year_id', $year->id)->pluck('id') : $ids;
    }

    public function canSeeStudentPii(Student $student): bool
    {
        if ($this->isSchoolLeader()) {
            return (int) $student->school_id === (int) $this->school_id;
        }
        if ($this->role === 'PARENT') {
            return $this->children()->where('students.id', $student->id)->exists();
        }
        if ($this->role === 'STUDENT') {
            return (int) $student->user_id === (int) $this->id;
        }

        if (! $student->schoolClass || (int) $student->school_id !== (int) $this->school_id) {
            return false;
        }
        // The class owner has full access. A subject teacher sees learners in the classes they teach.
        return (int) $student->schoolClass->class_teacher_id === (int) $this->id
            || ClassSubject::where('teacher_id', $this->id)->where('school_class_id', $student->school_class_id)->exists();
    }

    public function unreadNoticeCount(): int
    {
        return $this->notices()->whereNull('read_at')->count();
    }

    public function scopeLabel(): string
    {
        return match (true) {
            $this->role === 'EDM' => $this->division?->name ?? 'Division',
            $this->role === 'DEM' => $this->district?->name ?? 'District',
            $this->role === 'PEA' => ($this->zone?->name ?? 'Zone').' Zone',
            $this->role === 'SYSTEM_ADMIN' => 'National',
            default => $this->school?->name ?? '',
        };
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));

        return strtoupper(substr($parts[0] ?? '', 0, 1).substr(end($parts) ?: '', 0, 1));
    }
}
