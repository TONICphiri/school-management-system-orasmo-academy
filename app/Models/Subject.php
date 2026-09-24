<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use BelongsToSchool;
    protected $guarded = [];
    protected $casts = ["is_core" => "boolean", "is_optional" => "boolean"];

    public function department() { return $this->belongsTo(Department::class); }
    public function classSubjects() { return $this->hasMany(ClassSubject::class); }
}
