<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Mark extends Model
{
    use BelongsToSchool;
    protected $guarded = [];
    protected $casts = ["entered_at" => "datetime", "absent" => "boolean"];

    public function assessment() { return $this->belongsTo(Assessment::class); }
    public function student() { return $this->belongsTo(Student::class); }
    public function enteredBy() { return $this->belongsTo(User::class, "entered_by"); }
}
