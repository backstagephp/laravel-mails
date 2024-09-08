<?php

namespace Vormkracht10\Mails\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Discord\DiscordMessage;
use NotificationChannels\Telegram\TelegramMessage;
use Vormkracht10\Mails\Traits\HasDynamicDrivers;

class HighBounceRateNotification extends Notification implements ShouldQueue
{
    use HasDynamicDrivers, Queueable;

    protected $rate;

    protected $threshold;

    /**
     * @param  float|int  $rate
     * @param  float|int  $threshold
     */
    public function __construct($rate, $threshold)
    {
        $this->rate = $rate;

        $this->threshold = $threshold;
    }

    public function getTitle(): string
    {
        return 'Your app has a high mail bounce rate!';
    }

    public function getMessage(): string
    {
        $emoji = array_random([
            '🔥', '🧯', '‼️', '⁉️', '🔴', '📣', '😅', '🥵',
        ]);

        return "{$emoji} your app has a bounce rate of {$this->rate}%, the configured max is set at {$this->threshold}";
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