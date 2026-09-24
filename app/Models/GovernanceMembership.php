<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class GovernanceMembership extends Model
{
    use BelongsToSchool;
    protected $guarded = [];
    protected $casts = ["term_ends" => "date", "is_voting" => "boolean"];

    public const BODIES = ["SMC" => "School Management Committee", "PTA" => "Parent Teacher Association", "BOG" => "Board of Governors"];

    public function user() { return $this->belongsTo(User::class); }
}
