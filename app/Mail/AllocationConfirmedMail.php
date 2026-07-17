<?php

namespace App\Mail;

use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AllocationConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $recipient, public Group $group)
    {
    }

    public function build(): self
    {
        $allocation = $this->group->allocations()
            ->where('user_id', $this->recipient->id)
            ->with('room.hotel')
            ->first();

        return $this->subject('Your pilgrimage room allocation is confirmed')
            ->view('emails.allocation-confirmed', [
                'recipient' => $this->recipient,
                'group' => $this->group,
                'allocation' => $allocation,
            ]);
    }
}
