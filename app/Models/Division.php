<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    protected $guarded = [];

    public function districts() { return $this->hasMany(District::class); }
    public function schools() { return $this->hasMany(School::class); }
}
