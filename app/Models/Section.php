<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use BelongsToSchool;
    protected $guarded = [];

    public function head() { return $this->belongsTo(User::class, "head_id"); }
    public function classes() { return $this->hasMany(SchoolClass::class); }
}
