<?php

namespace App\Notifications;

use App\Models\User;
use App\Services\SiteSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent from the settings page to check the mail configuration.
 */
class TestMail extends Notification
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable instanceof User ? $notifiable->preferred_language : null;

        return (new MailMessage)
            ->subject(__('settings.mail.test_subject', ['app' => app(SiteSettings::class)->brand($locale)], $locale))
            ->line(__('settings.mail.test_line', [], $locale));
    }
}
