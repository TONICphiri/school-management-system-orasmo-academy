<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TimetableSlot extends Model
{
    use BelongsToSchool;
    protected $guarded = [];

    public const DAYS = [1 => "Monday", 2 => "Tuesday", 3 => "Wednesday", 4 => "Thursday", 5 => "Friday"];

    public function classSubject() { return $this->belongsTo(ClassSubject::class); }
}
