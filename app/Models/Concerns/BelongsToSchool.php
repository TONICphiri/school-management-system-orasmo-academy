<?php

namespace App\Models\Concerns;

use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $user = Auth::hasUser() ? Auth::user() : null;
            if ($user && $user->school_id) {
                $builder->where($builder->getModel()->getTable().'.school_id', $user->school_id);
            }
        });

        static::creating(function ($model) {
            $user = Auth::hasUser() ? Auth::user() : null;
            if (! $model->school_id && $user && $user->school_id) {
                $model->school_id = $user->school_id;
            }
            if ($user && $user->school_id && (int) $model->school_id !== (int) $user->school_id) {
                abort(403, 'Records cannot be written into another school.');
            }
        });
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
