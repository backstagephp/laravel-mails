<?php

namespace Backstage\Mails\Laravel\Database\Factories;

use Backstage\Mails\Laravel\Models\MailAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

class MailAttachmentFactory extends Factory
{
    protected $model = MailAttachment::class;

    public function definition(): array
    {
        return [
            'type' => '...',
            'ip' => '',
            'hostname' => '',
            'payload' => '',
        ];
    }
}
