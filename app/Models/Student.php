<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use BelongsToSchool;
    protected $guarded = [];
    protected $casts = ["date_of_birth" => "date", "admitted_on" => "date"];

    public function user() { return $this->belongsTo(User::class); }
    public function schoolClass() { return $this->belongsTo(SchoolClass::class); }
    public function guardians() { return $this->belongsToMany(User::class, "guardian_student")->withPivot("relationship"); }
    public function electives() { return $this->belongsToMany(ClassSubject::class, "student_subjects"); }
    public function marks() { return $this->hasMany(Mark::class); }
    public function attendances() { return $this->hasMany(Attendance::class); }

    public function fullName(): string
    {
        return $this->first_name." ".$this->last_name;
    }
}
