<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherProfile extends Model
{
    protected $guarded = [];
    protected $casts = ["first_appointment" => "date"];

    public const QUALIFICATIONS = [
        "T4" => "T4 Certificate",
        "T3" => "T3 Certificate",
        "T2" => "T2 Certificate",
        "DIPLOMA" => "Diploma in Education",
        "BACHELOR" => "Bachelor of Education",
        "MASTERS" => "Masters Degree",
    ];

    public const RANK = ["T4" => 1, "T3" => 2, "T2" => 3, "DIPLOMA" => 4, "BACHELOR" => 5, "MASTERS" => 6];

    public function user() { return $this->belongsTo(User::class); }

    public function rank(): int { return self::RANK[$this->qualification] ?? 0; }
}
