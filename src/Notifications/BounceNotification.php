<?php

namespace Backstage\Mails\Notifications;

use Backstage\Mails\Models\Mail;
use Backstage\Mails\Traits\HasDynamicDrivers;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Discord\DiscordMessage;
use NotificationChannels\Telegram\TelegramMessage;

class BounceNotification extends Notification implements ShouldQueue
{
    use HasDynamicDrivers;
    use Queueable;

    public function __construct(protected Mail $mail) {}

    public function getTitle(): string
    {
        return 'A Mail Has Bounced!';
    }

    public function getMessage(): string
    {
        $emoji = array_random([
            '🔥', '🧯', '‼️', '⁉️', '🔴', '📣', '😅', '🥵',
        ]);

        return "{$emoji} Mail has bounced";
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->greeting($this->getTitle())
            ->line($this->getMessage());
    }

    public function toDiscord(): DiscordMessage
    {
        return DiscordMessage::create($this->getMessage(), [
            'title' => $this->getTitle(),
            'color' => 0xF44336,
        ]);
    }

    public function toSlack(): SlackMessage
    {
        return (new SlackMessage)
            ->content($this->getMessage());
    }

    public function toTelegram(): TelegramMessage
    {
        return TelegramMessage::create()
            ->content($this->getMessage());
    }
}
