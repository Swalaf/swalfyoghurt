<?php

namespace App\Notifications;

use App\Models\License;
use App\Support\Settings;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseIssued extends Notification
{
    public function __construct(public License $license) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your :brand licence key', ['brand' => Settings::brand()]))
            ->greeting(__('Thanks for your purchase!'))
            ->line(__('Your :type licence key is:', ['type' => $this->license->type]))
            ->line('**'.$this->license->key.'**')
            ->line(__('Enter it in the installer (License step) or later in Admin → License & updates. It activates on one production domain; local and staging domains are free.'))
            ->line(__('Updates and support are included until :date.', ['date' => $this->license->supported_until?->toFormattedDateString() ?? '—']));
    }
}
