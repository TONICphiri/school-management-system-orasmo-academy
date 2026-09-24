<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    use BelongsToSchool;
    protected $guarded = [];

    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
    public function level() { return $this->belongsTo(Level::class); }
    public function section() { return $this->belongsTo(Section::class); }
    public function classTeacher() { return $this->belongsTo(User::class, "class_teacher_id"); }
    public function students() { return $this->hasMany(Student::class)->orderBy("last_name")->orderBy("first_name"); }
    public function classSubjects() { return $this->hasMany(ClassSubject::class); }

    public function name(): string
    {
        return trim(($this->level?->name ?? "")." ".$this->stream);
    }

    public function isSecondary(): bool
    {
        return $this->level?->phase === "SECONDARY";
    }
}
