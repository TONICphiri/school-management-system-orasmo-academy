<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $guarded = [];

    public function district() { return $this->belongsTo(District::class); }
    public function schools() { return $this->hasMany(School::class); }
}
