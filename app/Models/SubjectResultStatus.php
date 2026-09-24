<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class SubjectResultStatus extends Model
{
    use BelongsToSchool;
    protected $guarded = [];
    protected $casts = ["submitted_at" => "datetime", "validated_at" => "datetime"];

    public function classSubject() { return $this->belongsTo(ClassSubject::class); }
    public function submitter() { return $this->belongsTo(User::class, "submitted_by"); }
    public function validator() { return $this->belongsTo(User::class, "validated_by"); }
}
