<?php

namespace Backstage\Mails\Database\Factories;

use Backstage\Mails\Models\MailEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class MailEventFactory extends Factory
{
    protected $model = MailEvent::class;

    public function definition(): array
    {
        return [
            'type' => 'delivered',
            'payload' => [],
        ];
    }

    public function bounce(): Factory
    {
        return $this->state(function () {
            return [
                'type' => 'hard_bounced',
            ];
        });
    }
}
