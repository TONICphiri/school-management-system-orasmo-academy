<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use BelongsToSchool;
    protected $guarded = [];
    protected $casts = ["starts_on" => "date", "ends_on" => "date", "is_current" => "boolean"];

    public function terms() { return $this->hasMany(Term::class)->orderBy("number"); }
    public function classes() { return $this->hasMany(SchoolClass::class); }
}
