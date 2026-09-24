<?php

namespace App\Services;

use App\Models\OneTimeCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OtpService
{
    public const MAX_ATTEMPTS = 5;

    /**
     * Issue a new code and deliver it. Returns the plain code so the caller
     * can show it once on screen in local development only.
     */
    public static function issue(User $user, string $purpose = 'ACTIVATION', string $kind = 'OTP', ?string $channel = null): string
    {
        $channel = strtoupper($channel ?: $user->preferred_channel ?: 'EMAIL');

        OneTimeCode::where('user_id', $user->id)->where('purpose', $purpose)->whereNull('used_at')
            ->update(['used_at' => now()]);

        if ($kind === 'TEMP_PASSWORD') {
            $plain = self::temporaryPassword();
            $expires = now()->addHours(72);
            $expiryText = '72 hours';
        } else {
            $plain = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = now()->addMinutes(15);
            $expiryText = '15 minutes';
        }

        OneTimeCode::create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'kind' => $kind,
            'channel' => $channel,
            'code_hash' => Hash::make($plain),
            'expires_at' => $expires,
        ]);

        $label = $kind === 'TEMP_PASSWORD' ? 'temporary password' : 'one-time code';
        $intro = $purpose === 'LOGIN' ? 'Your MoEST SMS sign-in' : 'Your MoEST SMS activation';
        $body = $intro.' '.$label.' is '.$plain.'. It expires in '.$expiryText.'. Do not share it with anyone.';
        $masked = $intro.' '.$label.' is '.str_repeat('*', strlen($plain)).'. It expires in '.$expiryText.'.';

        MessageGateway::send($user, $channel, 'MoEST School Management System', $body, $masked);

        return $plain;
    }

    /**
     * @return string one of OK, INVALID, EXPIRED, LOCKED
     */
    public static function verify(User $user, string $code, string $purpose = 'ACTIVATION'): string
    {
        $otp = OneTimeCode::where('user_id', $user->id)->where('purpose', $purpose)
            ->whereNull('used_at')->latest('id')->first();

        if (! $otp) {
            return 'EXPIRED';
        }
        if ($otp->attempts >= self::MAX_ATTEMPTS) {
            return 'LOCKED';
        }
        if ($otp->expires_at->isPast()) {
            return 'EXPIRED';
        }

        if (! Hash::check(trim($code), $otp->code_hash)) {
            $otp->increment('attempts');
            if ($otp->attempts >= self::MAX_ATTEMPTS) {
                $otp->update(['used_at' => now()]);
                Notifier::send(
                    Notifier::systemAdmins(),
                    'SECURITY',
                    'Repeated failed code attempts',
                    $user->name.' ('.$user->roleLabel().($user->school ? ', '.$user->school->name : '').') entered a wrong '.strtolower($purpose).' code '.self::MAX_ATTEMPTS.' times. The code has been cancelled.',
                    route('admin.audit'),
                    'HIGH'
                );
                Audit::log('security.otp_locked', 'One-time code locked after '.self::MAX_ATTEMPTS.' failed attempts for '.$user->name, $user, $user->school_id);

                return 'LOCKED';
            }

            return 'INVALID';
        }

        $otp->update(['used_at' => now()]);

        return 'OK';
    }

    protected static function temporaryPassword(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $out = '';
        for ($i = 0; $i < 10; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $out;
    }
}
