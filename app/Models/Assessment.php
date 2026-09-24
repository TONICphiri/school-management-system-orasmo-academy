<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use BelongsToSchool;
    protected $guarded = [];
    protected $casts = ["held_on" => "date"];

    public function classSubject() { return $this->belongsTo(ClassSubject::class); }
    public function term() { return $this->belongsTo(Term::class); }
    public function marks() { return $this->hasMany(Mark::class); }
}
