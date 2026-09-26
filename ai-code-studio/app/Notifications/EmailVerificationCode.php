<?php

namespace App\Notifications;

use App\Support\Settings;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationCode extends Notification
{
    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your '.Settings::brand().' verification code')
            ->greeting('Hi '.$notifiable->firstName().',')
            ->line('Your verification code is:')
            ->line('**'.$this->code.'**')
            ->line('It expires in 30 minutes. If you didn’t create an account, you can ignore this email.');
    }
}
