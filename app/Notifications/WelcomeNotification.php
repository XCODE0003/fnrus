<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Welcome to our application')
            ->greeting('Hello ' . $notifiable->name)
            ->line('Welcome to our application. We are excited to have you on board!')
            ->action('Start Exploring', url('/'))
            ->line('Thank you for using our application!');
    }
}
