<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use BelongsToSchool;
    protected $guarded = [];
    protected $casts = ["attended_on" => "date"];

    public function student() { return $this->belongsTo(Student::class); }
}
