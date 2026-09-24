<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ReportComment extends Model
{
    use BelongsToSchool;
    protected $guarded = [];
}
