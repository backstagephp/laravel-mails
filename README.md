# Keep track of all events on sent emails in Laravel and get notified when something is wrong

[![Total Downloads](https://img.shields.io/packagist/dt/backstage/laravel-mails.svg?style=flat-square)](https://packagist.org/packages/backstage/laravel-mails)
[![Tests](https://github.com/backstagephp/laravel-mails/actions/workflows/pest.yml/badge.svg?branch=main)](https://github.com/backstagephp/laravel-mails/actions/workflows/pest.yml)
[![PHPStan](https://github.com/backstagephp/laravel-mails/actions/workflows/phpstan.yml/badge.svg?branch=main)](https://github.com/backstagephp/laravel-mails/actions/workflows/phpstan.yml)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/backstagephp/laravel-mails)
![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/backstage/laravel-mails)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/backstage/laravel-mails.svg?style=flat-square)](https://packagist.org/packages/backstage/laravel-mails)

## Nice to meet you, we're [Backstage](https://backstagephp.com)

Hi! We are a web development agency from Nijmegen in the Netherlands and we use Laravel for everything: advanced websites with a lot of bells and whitles and large web applications.

## Why this package

Email as a protocol is very error prone. Succesfull email delivery is not guaranteed in any way, so it is best to monitor your email sending realtime. Using external services like Postmark or Mailgun, email gets better by offering things like logging and delivery feedback, but it still needs your attention and can fail silently but horendously. Therefore we created Laravel Mails that fills in all the gaps.

Using Laravel we create packages to scratch a lot of our own itches, as we get to certain challenges working for our clients and on our projects. One of our problems in our 13 years of web development experience is customers that contact us about emails not getting delivered.

Sometimes this happens because of a bug in code, but often times it's because of things going wrong you can't imagine before hand. If it can fail, it will fail. Using Murphy's law in full extend! And email is one of these types where this happens more than you like.

As we got tired of the situation that a customer needs to call us, we want to know before the customer can notice it and contact us. Therefore we created this package: to log all events happening with our sent emails and to get automatically notified using Discord (or Slack, Telegram) when there are problems on the horizon.

## Features

Laravel Mails can collect everything you might want to track about the mails that has been sent by your Laravel app. Common use cases are provided in this package:

-   Log all sent emails, attachments and events with only specific attributes
-   Works currently for popular email service providers Postmark and Mailgun
-   Collect feedback about the delivery status from email providers using webhooks
-   Get quickly and automatically notified when email hard/soft bounces or the bouncerate goes too high
-   Prune all logged emails periodically to keep the database nice and slim
-   Resend logged emails to another recipient
-   View all sent emails in the browser using complementary package [Filament Mails](https://github.com/backstagephp/filament-mails)

## Upcoming features

-   We can write drivers for popular email service providers like Resend, SendGrid, Amazon SES and Mailtrap.
-   Relate emails being send in Laravel directly to Eloquent models, for example the order confirmation email attached to an Order model.

## Looking for a UI? We've got your back: [Filament Mails](https://github.com/backstagephp/filament-mails)

We created a Laravel [Filament](https://filamentphp.com) plugin called [Filament Mails](https://github.com/backstagephp/filament-mails) to easily view all data collected by this Laravel Mails package.

It can show all information about the emails and events in a beautiful UI:

![Filament Mails](https://raw.githubusercontent.com/backstagephp/filament-mails/main/docs/mails-list.png)

## Installation

First install the package via composer:

```bash
composer require backstage/laravel-mails
```

Then you can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="mails-migrations"
php artisan migrate
```

Add the API key of your email service provider to the `config/services.php` file in your Laravel project, currently we only support Postmark and Mailgun:

```php
'postmark' => [
    'token' => env('POSTMARK_TOKEN'),
],

'mailgun' => [
    'domain' => env('MAILGUN_DOMAIN'),
    'secret' => env('MAILGUN_SECRET'),
    'webhook_signing_key' => env('MAILGUN_WEBHOOK_SIGNING_KEY'),
    'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    'scheme' => 'https',
],

'ses' => [
    // You should already have these set up by Laravel's default installation
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    
    // This one is package-specific
    'configuration_set_name' => env('AWS_SES_CONFIGURATION_SET', 'laravel-mails-ses-webhook'),
    'account_id' => env('AWS_ACCOUNT_ID', ''), // Your AWS account id
    'scheme' => 'https', // 'http' or 'https',
    'verify_signature' => true,
],
```

When done, run this command with the slug of your service provider:

```bash
php artisan mail:webhooks [service] // where [service] is your provider, e.g. postmark or mailgun
```

And for changing the configuration you can publish the config file with:

```bash
php artisan vendor:publish --tag="mails-config"
```

This is the contents of the published config file:

```php
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
]
```

### [Optional] Amazon SES

When using Amazon SES, you also require the following dependencies

```bash
composer require aws/aws-sdk-php aws/aws-php-sns-message-validator
```

You aws ses user should also have the authorization to create SNS topics 

## Usage

### Logging

When you send emails within Laravel using the `Mail` Facade or using a `Mailable`, Laravel Mails will log the email sending and all events that are incoming from your email service provider.

### Relate emails to Eloquent models

...

### Resend a logged email

...

### Get notified of important events such as bounces, high bounce rate or spam complaints

...

### Prune logged emails

...

## Events

Depending on the mail provider, we send these events comming in from the webhooks of the email service provider.

```php
Backstage\Mails\Events\MailAccepted::class,
Backstage\Mails\Events\MailClicked::class,
Backstage\Mails\Events\MailComplained::class,
Backstage\Mails\Events\MailDelivered::class,
Backstage\Mails\Events\MailEvent::class,
Backstage\Mails\Events\MailEventLogged::class,
Backstage\Mails\Events\MailHardBounced::class,
Backstage\Mails\Events\MailOpened::class,
Backstage\Mails\Events\MailResent::class,
Backstage\Mails\Events\MailSoftBounced::class,
Backstage\Mails\Events\MailUnsubscribed::class,
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Mark van Eijk](https://github.com/markvaneijk)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
