<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Default SMS gateway: logs the message instead of calling a real provider. Swap the
 * SmsGateway binding in AppServiceProvider for a Twilio/MSG91/etc implementation once
 * provider credentials are available — the rest of the notification pipeline is unchanged.
 */
class LogSmsGateway implements SmsGateway
{
    public function send(string $toPhoneNumber, string $message): bool
    {
        if (blank($toPhoneNumber)) {
            return false;
        }

        Log::info('SMS dispatched', ['to' => $toPhoneNumber, 'message' => $message]);

        return true;
    }
}
