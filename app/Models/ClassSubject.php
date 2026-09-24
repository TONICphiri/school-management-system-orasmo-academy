<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ClassSubject extends Model
{
    use BelongsToSchool;
    protected $guarded = [];

    public function schoolClass() { return $this->belongsTo(SchoolClass::class); }
    public function subject() { return $this->belongsTo(Subject::class); }
    public function teacher() { return $this->belongsTo(User::class, "teacher_id"); }
    public function assessments() { return $this->hasMany(Assessment::class); }
    public function slots() { return $this->hasMany(TimetableSlot::class); }
    public function electiveStudents() { return $this->belongsToMany(Student::class, "student_subjects"); }

    public function isElective(): bool
    {
        return $this->subject && ! $this->subject->is_core && $this->schoolClass?->isSecondary();
    }

    public function roster()
    {
        if ($this->isElective()) {
            return $this->electiveStudents()->orderBy("last_name")->get();
        }

        return $this->schoolClass->students()->where("status", "ENROLLED")->get();
    }

    public function statusFor(Term $term): SubjectResultStatus
    {
        return SubjectResultStatus::firstOrCreate(
            ["class_subject_id" => $this->id, "term_id" => $term->id],
            ["school_id" => $this->school_id, "status" => "DRAFT"]
        );
    }
}
