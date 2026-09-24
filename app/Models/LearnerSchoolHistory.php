<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class LearnerSchoolHistory extends Model
{
    use BelongsToSchool;
    protected $guarded = [];

    public function student() { return $this->belongsTo(Student::class); }
}
