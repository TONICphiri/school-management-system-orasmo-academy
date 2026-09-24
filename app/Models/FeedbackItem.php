<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class FeedbackItem extends Model
{
    use BelongsToSchool;
    protected $guarded = [];
    protected $casts = ["responded_at" => "datetime", "escalated_at" => "datetime"];

    public function author() { return $this->belongsTo(User::class, "user_id"); }
    public function responder() { return $this->belongsTo(User::class, "responded_by"); }
    public function escalatedTo() { return $this->belongsTo(User::class, "escalated_to"); }
}
