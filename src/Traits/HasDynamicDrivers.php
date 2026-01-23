<?php

namespace Backstage\Mails\Traits;

use Illuminate\Notifications\Channels\MailChannel;
use NotificationChannels\Discord\DiscordChannel;

trait HasDynamicDrivers
{
    protected array $drivers = [];

    public function via(): array
    {
        return $this->drivers;
    }

    /**
     * @param  string|string[]  $drivers
     */
    public function on($drivers, bool $merge = false): static
    {
        $drivers = array_wrap($drivers);

        if ($merge) {
            $drivers = array_merge($this->drivers, $drivers);
        }

        $via = [
            'discord' => DiscordChannel::class,
            'mail' => MailChannel::class,
        ];

        $drivers = array_map(fn ($driver): string => $via[$driver], $drivers);

        $this->drivers = $drivers;

        return $this;
    }
}
