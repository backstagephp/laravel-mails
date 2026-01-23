<?php

use Backstage\Mails\Models\Mail;
use Backstage\Mails\Models\MailAttachment;
use Backstage\Mails\Models\MailEvent;

return [

    // Eloquent model to use for sent emails

    'models' => [
        'mail' => Mail::class,
        'event' => MailEvent::class,
        'attachment' => MailAttachment::class,
    ],

    // Table names for saving sent emails and polymorphic relations to database

    'database' => [
        'tables' => [
            'mails' => 'mails',
            'attachments' => 'mail_attachments',
            'events' => 'mail_events',
            'polymorph' => 'mailables',
        ],

        'pruning' => [
            'enabled' => true,
            'after' => 30, // days
        ],
    ],

    'headers' => [
        'uuid' => 'X-Mails-UUID',

        'associate' => 'X-Mails-Associated-Models',
    ],

    'webhooks' => [
        'routes' => [
            'prefix' => 'webhooks/mails',
        ],

        'queue' => env('MAILS_QUEUE_WEBHOOKS', false),
    ],

    // Logging mails
    'logging' => [

        // Enable logging of all sent mails to database

        'enabled' => env('MAILS_LOGGING_ENABLED', true),

        // Specify attributes to log in database

        'attributes' => [
            'subject',
            'from',
            'to',
            'reply_to',
            'cc',
            'bcc',
            'html',
            'text',
        ],

        // Encrypt all attributes saved to database

        'encrypted' => env('MAILS_ENCRYPTED', true),

        // Track following events using webhooks from email provider

        'tracking' => [
            'bounces' => true,
            'clicks' => true,
            'complaints' => true,
            'deliveries' => true,
            'opens' => true,
            'unsubscribes' => true,
        ],

        // Enable saving mail attachments to disk

        'attachments' => [
            'enabled' => env('MAILS_LOGGING_ATTACHMENTS_ENABLED', true),
            'disk' => env('FILESYSTEM_DISK', 'local'),
            'root' => 'mails/attachments',
        ],
    ],

    // Notifications for important mail events

    'notifications' => [
        'mail' => [
            'to' => ['test@example.com'],
        ],

        'discord' => [
            // 'to' => ['1234567890'],
        ],

        'slack' => [
            // 'to' => ['https://hooks.slack.com/services/...'],
        ],

        'telegram' => [
            // 'to' => ['1234567890'],
        ],
    ],

    'events' => [
        'soft_bounced' => [
            'notify' => ['mail'],
        ],

        'hard_bounced' => [
            'notify' => ['mail'],
        ],

        'bouncerate' => [
            'notify' => [],

            'retain' => 30, // days

            'treshold' => 1, // %
        ],

        'deliveryrate' => [
            'treshold' => 99,
        ],

        'complained' => [
            //
        ],

        'unsent' => [
            //
        ],
    ],

];
