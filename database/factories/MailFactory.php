<?php

namespace Backstage\Mails\Database\Factories;

use Backstage\Mails\Models\Mail;
use Illuminate\Database\Eloquent\Factories\Factory;

class MailFactory extends Factory
{
    protected $model = Mail::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid,
            'mailer' => 'smtp',
            'mail_class' => '',
            'subject' => $this->faker->sentence(5),
            'from' => [
                $this->faker->email => $this->faker->firstName(),
            ],
            'reply_to' => null,
            'to' => [
                $this->faker->email => $this->faker->firstName(),
            ],
            'cc' => null,
            'bcc' => null,
            'sent_at' => now(),
            'delivered_at' => null,
            'last_opened_at' => null,
            'last_clicked_at' => null,
            'complained_at' => null,
            'soft_bounced_at' => null,
            'hard_bounced_at' => null,
        ];
    }

    public function hasCc(): MailFactory
    {
        return $this->state(function () {
            return [
                'cc' => [
                    $this->faker->email => $this->faker->firstName(),
                ],
            ];
        });
    }

    public function hasBcc(): MailFactory
    {
        return $this->state(function () {
            return [
                'bcc' => [
                    $this->faker->email => $this->faker->firstName(),
                ],
            ];
        });
    }
}
