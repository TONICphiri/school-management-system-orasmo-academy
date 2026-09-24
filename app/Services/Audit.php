<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class Audit
{
    public static function log(string $action, string $description, $target = null, ?int $schoolId = null): void
    {
        $user = Auth::user();

        AuditLog::create([
            'school_id' => $schoolId ?? $target?->school_id ?? $user?->school_id,
            'user_id' => $user?->id,
            'action' => $action,
            'target_type' => $target ? class_basename($target) : null,
            'target_id' => $target?->id,
            'description' => $description,
            'ip_address' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 250),
            'created_at' => now(),
        ]);
    }
}
