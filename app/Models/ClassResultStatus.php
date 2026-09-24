<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ClassResultStatus extends Model
{
    use BelongsToSchool;
    protected $guarded = [];
    protected $casts = ["class_reviewed_at" => "datetime", "deputy_approved_at" => "datetime", "released_at" => "datetime"];

    public function schoolClass() { return $this->belongsTo(SchoolClass::class); }
    public function term() { return $this->belongsTo(Term::class); }
}
