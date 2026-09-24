<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    protected $guarded = [];
    protected $casts = ["read_at" => "datetime"];

    public const CATEGORIES = [
        "ACCOUNT" => "Account",
        "SECURITY" => "Security",
        "RESULTS" => "Results",
        "ASSIGNMENT" => "Assignment",
        "FEEDBACK" => "Feedback",
        "INSPECTION" => "Inspection",
        "REPORT" => "Report",
        "CALENDAR" => "Calendar",
        "GENERAL" => "General",
    ];

    public const ICONS = [
        "ACCOUNT" => "user", "SECURITY" => "shield", "RESULTS" => "check-square", "ASSIGNMENT" => "grid",
        "FEEDBACK" => "message", "INSPECTION" => "clipboard", "REPORT" => "file", "CALENDAR" => "calendar", "GENERAL" => "bell",
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function school() { return $this->belongsTo(School::class); }
}
