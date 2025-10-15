<?php

namespace Backstage\Mails\Traits;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as Notifications;

trait SendsNotifications
{
    public function send(Notification $notification, array $channels): void
    {
        foreach ($channels as $channel) {
            $key = implode('.', ['mails', 'notifications', $channel, 'to']);

            $accounts = array_wrap(
                config($key, []),
            );

            if (empty($accounts)) {
                continue;
            }

            foreach ($accounts as $account) {
                Notifications::route($channel, $account)->notify(
                    $this->prepareNotification($notification, $channel)
                );
            }
        }
    }

    /**
     * Prepare the notification for sending.
     */
    protected function prepareNotification(Notification $notification, string $channel): Notification
    {
        if (method_exists($notification, 'on')) {
            return $notification->on($channel);
        }

        return $notification;
    }
}
