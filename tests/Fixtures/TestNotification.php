<?php

namespace Backstage\Mails\Laravel\Tests\Fixtures;

use Backstage\Mails\Laravel\Messages\AssociatedMailMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

class TestNotification extends Notification
{
    /**
     * @param  array<Model>  $models
     */
    public function __construct(
        protected array $models = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): AssociatedMailMessage
    {
        $message = (new AssociatedMailMessage)
            ->from('from@example.com')
            ->subject('Test Notification')
            ->line('This is a test notification.');

        if (! empty($this->models)) {
            $message->associateModels($this->models);
        }

        return $message;
    }
}
