<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Committee extends Model
{
    use BelongsToSchool;
    protected $guarded = [];

    public function members() { return $this->belongsToMany(User::class)->withPivot("position"); }
}
