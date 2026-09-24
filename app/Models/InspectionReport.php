<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InspectionReport extends Model
{
    protected $guarded = [];
    protected $casts = ["visit_date" => "date", "follow_up_by" => "date", "flag_follow_up" => "boolean"];

    public function school() { return $this->belongsTo(School::class); }
    public function supervisor() { return $this->belongsTo(User::class, "supervisor_id"); }
}
