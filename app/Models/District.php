<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $guarded = [];

    public function division() { return $this->belongsTo(Division::class); }
    public function zones() { return $this->hasMany(Zone::class); }
    public function schools() { return $this->hasMany(School::class); }
}
