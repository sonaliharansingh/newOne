<?php

namespace App\Services;

interface SmsGateway
{
    /**
     * Send an SMS. Returns true on success, false on failure.
     */
    public function send(string $toPhoneNumber, string $message): bool;
}
