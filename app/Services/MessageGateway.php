<?php

namespace App\Services;

use App\Models\MessageLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MessageGateway
{
    /**
     * Send a message through SMS or email. When $maskedBody is given, that
     * version is what gets stored in the outbox so codes are never kept.
     */
    public static function send(User $user, string $channel, string $subject, string $body, ?string $maskedBody = null): void
    {
        $channel = strtoupper($channel);
        $recipient = $channel === 'SMS' ? $user->phone : $user->email;

        if (! $recipient) {
            $channel = $channel === 'SMS' ? 'EMAIL' : 'SMS';
            $recipient = $channel === 'SMS' ? $user->phone : $user->email;
        }
        if (! $recipient) {
            return;
        }

        $log = MessageLog::create([
            'school_id' => $user->school_id,
            'user_id' => $user->id,
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => $subject,
            'body' => $maskedBody ?? $body,
            'status' => 'QUEUED',
        ]);

        try {
            if ($channel === 'EMAIL') {
                Mail::raw($body, function ($m) use ($recipient, $subject) {
                    $m->to($recipient)->subject($subject);
                });
            } else {
                self::sms($recipient, $body);
            }
            $log->update(['status' => 'SENT']);
        } catch (\Throwable $e) {
            $log->update(['status' => 'FAILED', 'provider_reference' => substr($e->getMessage(), 0, 250)]);
        }
    }

    protected static function sms(string $phone, string $message): void
    {
        $driver = config('services.sms.driver', 'log');

        if ($driver === 'africastalking') {
            Http::asForm()
                ->withHeaders(['apiKey' => config('services.sms.key'), 'Accept' => 'text/plain'])
                ->post('https://api.africastalking.com/version1/messaging', [
                    'username' => config('services.sms.username'),
                    'to' => $phone,
                    'message' => $message,
                    'from' => config('services.sms.sender'),
                ])->throw();

            return;
        }

        Log::channel('single')->info('SMS to '.$phone.': '.$message);
    }
}
