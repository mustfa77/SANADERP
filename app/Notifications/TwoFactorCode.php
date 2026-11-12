<?php

namespace App\Notifications;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Hash;

/**
 * Emails the one-time login code. The code is stored hashed on the user.
 */
class TwoFactorCode extends Notification
{
    use Queueable;

    public function __construct(public string $code) {}

    /**
     * Code validity in minutes (settings: two_factor_code_minutes).
     */
    public static function expiresInMinutes(): int
    {
        return Setting::integer('two_factor_code_minutes');
    }

    /**
     * Generate a new code for the user and return it in plain text.
     */
    public static function issue(User $user): string
    {
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'two_factor_code' => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(self::expiresInMinutes()),
        ])->save();

        return $code;
    }

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
            ->subject(__('auth.two_factor.mail_subject', [], $locale))
            ->line(__('auth.two_factor.mail_line', [], $locale))
            ->line($this->code)
            ->line(__('auth.two_factor.mail_expiry', ['minutes' => self::expiresInMinutes()], $locale));
    }
}
