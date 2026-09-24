<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    protected $guarded = [];

    public const TYPES = ['PRIMARY' => 'Primary', 'SECONDARY' => 'Secondary', 'COMBINED' => 'Combined'];

    public const CATEGORIES = [
        'GOVERNMENT' => 'Government',
        'GRANT_AIDED' => 'Grant-Aided',
        'CDSS' => 'Community Day Secondary School',
        'PRIVATE' => 'Private',
    ];

    public const STATUSES = ['ACTIVE' => 'Active', 'SUSPENDED' => 'Suspended', 'PENDING_ACTIVATION' => 'Pending Activation'];

    public const STRUCTURES = ['8-4-4' => '8-4-4 (Standard 1 to 8, Form 1 to 4)', '1-6-6-3' => '1-6-6-3 (Preparatory, Standard 1 to 6, Form 1 to 6)'];

    public function division() { return $this->belongsTo(Division::class); }
    public function district() { return $this->belongsTo(District::class); }
    public function zone() { return $this->belongsTo(Zone::class); }
    public function users() { return $this->hasMany(User::class); }
    public function academicYears() { return $this->hasMany(AcademicYear::class)->withoutGlobalScopes()->orderByDesc('starts_on'); }
    public function levels() { return $this->hasMany(Level::class)->withoutGlobalScopes()->orderBy('phase')->orderBy('ordinal'); }
    public function students() { return $this->hasMany(Student::class)->withoutGlobalScopes(); }
    public function inspections() { return $this->hasMany(InspectionReport::class); }

    public function hasPrimary(): bool { return in_array($this->type, ['PRIMARY', 'COMBINED']); }
    public function hasSecondary(): bool { return in_array($this->type, ['SECONDARY', 'COMBINED']); }

    public function headTeacher()
    {
        return $this->hasOne(User::class)->where('role', 'FACILITY_ADMIN');
    }

    public function currentYear(): ?AcademicYear
    {
        return AcademicYear::withoutGlobalScopes()->where('school_id', $this->id)->where('is_current', true)->first();
    }

    public function currentTerm(): ?Term
    {
        return Term::withoutGlobalScopes()->where('school_id', $this->id)->where('is_current', true)->first();
    }

    public function typeLabel(): string { return self::TYPES[$this->type] ?? $this->type; }
    public function categoryLabel(): string { return self::CATEGORIES[$this->category] ?? $this->category; }
    public function statusLabel(): string { return self::STATUSES[$this->status] ?? $this->status; }

    public function directorate(): string
    {
        return $this->type === 'PRIMARY' ? 'Directorate of Basic Education' : 'Directorate of Secondary Education';
    }
}
