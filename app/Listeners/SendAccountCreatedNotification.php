<?php

namespace App\Listeners;

use App\Events\UserAccountCreated;
use App\Notifications\AccountCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queued: sending mail must not slow down or break account creation.
 */
class SendAccountCreatedNotification implements ShouldQueue
{
    public int $tries = 3;

    public int $backoff = 60;

    public function handle(UserAccountCreated $event): void
    {
        $event->user->notify(new AccountCreatedNotification);
    }
}
