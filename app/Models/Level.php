<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    use BelongsToSchool;
    protected $guarded = [];

    public function classes() { return $this->hasMany(SchoolClass::class); }
}
