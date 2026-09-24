<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    use BelongsToSchool;
    protected $guarded = [];
    protected $casts = ["starts_on" => "date", "ends_on" => "date", "is_current" => "boolean"];

    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
    public function breaks() { return $this->hasMany(TermBreak::class); }

    public function label(): string
    {
        return "Term ".$this->number.", ".$this->academicYear?->name;
    }
}
