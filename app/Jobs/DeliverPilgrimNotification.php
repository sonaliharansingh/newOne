<?php

namespace App\Jobs;

use App\Mail\AllocationConfirmedMail;
use App\Models\PilgrimNotification;
use App\Services\SmsGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Delivers one pending notifications row (mail or sms) and updates its status. Dashboard
 * notifications don't need delivery — they're marked sent immediately when created, since
 * they're just an in-app record.
 */
class DeliverPilgrimNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public int $notificationId)
    {
    }

    public function handle(SmsGateway $smsGateway): void
    {
        $notification = PilgrimNotification::with(['user', 'group'])->find($this->notificationId);

        if (! $notification || $notification->status !== 'pending') {
            return;
        }

        try {
            $delivered = match ($notification->type) {
                'email' => $this->deliverEmail($notification),
                'sms' => $smsGateway->send($notification->user->phone, $notification->message),
                default => true,
            };

            $notification->update([
                'status' => $delivered ? 'sent' : 'failed',
                'sent_at' => $delivered ? now() : null,
            ]);
        } catch (Throwable $e) {
            $notification->update(['status' => 'failed']);
        }
    }

    private function deliverEmail(PilgrimNotification $notification): bool
    {
        if (blank($notification->user->email) || ! $notification->group) {
            return false;
        }

        Mail::to($notification->user->email)->send(
            new AllocationConfirmedMail($notification->user, $notification->group)
        );

        return true;
    }
}
