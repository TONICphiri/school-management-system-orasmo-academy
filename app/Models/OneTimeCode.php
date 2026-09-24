<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OneTimeCode extends Model
{
    protected $guarded = [];
    protected $casts = ["expires_at" => "datetime", "used_at" => "datetime"];

    public function user() { return $this->belongsTo(User::class); }

    public function isUsable(): bool
    {
        return is_null($this->used_at) && $this->expires_at->isFuture() && $this->attempts < 5;
    }
}
