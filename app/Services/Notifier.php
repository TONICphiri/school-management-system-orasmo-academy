<?php

namespace App\Services;

use App\Models\Notice;
use App\Models\User;
use Illuminate\Support\Collection;

class Notifier
{
    /**
     * @param  User|iterable<User>  $users
     * @param  array<int,string>  $channels  any of APP, SMS, EMAIL, PREFERRED
     */
    public static function send($users, string $category, string $title, string $body, ?string $link = null, string $priority = 'NORMAL', array $channels = ['APP']): int
    {
        $users = $users instanceof User ? collect([$users]) : Collection::wrap($users);
        $count = 0;

        foreach ($users->filter()->unique('id') as $user) {
            if (in_array('APP', $channels, true)) {
                Notice::create([
                    'user_id' => $user->id,
                    'school_id' => $user->school_id,
                    'category' => $category,
                    'title' => $title,
                    'body' => $body,
                    'link' => $link,
                    'priority' => $priority,
                ]);
            }

            foreach (['SMS', 'EMAIL', 'PREFERRED'] as $ch) {
                if (in_array($ch, $channels, true)) {
                    $real = $ch === 'PREFERRED' ? $user->preferred_channel : $ch;
                    MessageGateway::send($user, $real, $title, $title.'. '.$body);
                }
            }
            $count++;
        }

        return $count;
    }

    public static function systemAdmins(): Collection
    {
        return User::where('role', 'SYSTEM_ADMIN')->where('status', 'ACTIVE')->get();
    }

    public static function schoolRoles(int $schoolId, array $roles): Collection
    {
        return User::where('school_id', $schoolId)->whereIn('role', $roles)->where('status', 'ACTIVE')->get();
    }
}
