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
-   Works currently for popular email service providers Postmark, Mailgun, Resend and Amazon SES
-   Collect feedback about the delivery status from email providers using webhooks
-   Get quickly and automatically notified when email hard/soft bounces or the bouncerate goes too high
-   Prune all logged emails periodically to keep the database nice and slim
-   Resend logged emails to another recipient
-   View all sent emails in the browser using complementary package [Filament Mails](https://github.com/backstagephp/filament-mails)

## Upcoming features

-   We can write drivers for more email service providers like SendGrid and Mailtrap.
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

Add the API key of your email service provider to the `config/services.php` file in your Laravel project. We currently support Postmark, Mailgun, Resend and Amazon SES:

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
    'scheme' => 'https', // 'http' or 'https'
],
```

When done, run this command with the slug of your service provider:

```bash
php artisan mail:webhooks [service] // where [service] is your provider, e.g. postmark, mailgun, resend or ses
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
        'notify' => [],
    ],

    'unsent' => [
        //
    ],
]
```

### Setting up Amazon SES

Amazon SES needs a little more setup than the other providers, because SES does not post events to your app directly: it publishes them to an SNS topic, which then delivers them to your webhook.

#### 1. Install the AWS dependencies

The AWS SDK is optional, so install it yourself when you use SES:

```bash
composer require aws/aws-sdk-php aws/aws-php-sns-message-validator
```

#### 2. Configure the SES mailer

Use the SES mailer that ships with Laravel and fill in your AWS credentials:

```dotenv
MAIL_MAILER=ses

AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=eu-west-1
AWS_ACCOUNT_ID=123456789012
```

Make sure the address you send from is a verified identity in the SES console, and that your account is out of the SES sandbox before sending to arbitrary recipients.

#### 3. Add the SES service configuration

Add the `ses` block shown in the [installation section](#installation) to `config/services.php`. Besides the credentials Laravel already uses, this package reads:

| Key | Description |
| --- | --- |
| `configuration_set_name` | Name of the SES configuration set and SNS topic the package creates. Defaults to `laravel-mails-ses-webhook`. |
| `account_id` | Your AWS account id, used to grant SES permission to publish to the SNS topic. |
| `scheme` | The protocol SNS uses to deliver to your webhook: `https` (recommended) or `http`. |

#### 4. Grant the right IAM permissions

The user whose credentials you configured needs to send mail, manage the configuration set, and create the SNS topic:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "ses:SendRawEmail",
                "ses:CreateConfigurationSet",
                "ses:CreateConfigurationSetEventDestination",
                "ses:DeleteConfigurationSetEventDestination",
                "ses:DeleteSuppressedDestination",
                "sns:CreateTopic",
                "sns:AddPermission",
                "sns:Subscribe"
            ],
            "Resource": "*"
        }
    ]
}
```

`ses:DeleteSuppressedDestination` is only needed if you want to remove addresses from the SES account-level suppression list from within your app.

#### 5. Register the webhooks

```bash
php artisan mail:webhooks ses
```

This creates the configuration set, creates the SNS topic, allows SES to publish to it, points the configuration set's event destination at the topic, and subscribes your webhook URL to it. The command is idempotent, so you can safely run it again.

Which events get registered depends on the `mails.logging.tracking` config: only the ones you enabled are subscribed to.

#### 6. Confirm the SNS subscription

SNS immediately posts a `SubscriptionConfirmation` message to your webhook, which the package confirms for you. For this to work:

-   your webhook URL must be publicly reachable (SNS cannot reach `localhost`), and
-   your queue worker must be running if `QUEUE_CONNECTION` is not `sync`, since incoming webhooks are processed on the queue.

You can check the subscription status in the SNS console under the topic named after your configuration set. If it is still `PendingConfirmation`, the confirmation request never reached your app.

#### How events are matched

SES only publishes events for mails that were sent under the configuration set your webhook is registered on. This package therefore adds an `X-SES-CONFIGURATION-SET` header to every outgoing mail. If you already configured a set yourself via `mail.mailers.ses.options.ConfigurationSetName`, that one is left untouched:

```php
'ses' => [
    'transport' => 'ses',
    'options' => [
        'ConfigurationSetName' => 'my-own-configuration-set',
    ],
],
```

In that case, register the webhooks against the same name by setting `services.ses.configuration_set_name` to it.

## Usage

### Logging

When you send emails within Laravel using the `Mail` Facade or using a `Mailable`, Laravel Mails will log the email sending and all events that are incoming from your email service provider.

Logging happens automatically, so there is nothing to call yourself. Which attributes end up in the database is up to you: `mails.logging.attributes` decides what is stored, and `mails.logging.encrypted` encrypts those attributes at rest. Set `mails.logging.enabled` to `false` to switch logging off entirely, and use `mails.logging.attachments` to control whether attachments are copied to disk.

Every logged mail is a `Mail` model, with the events that came in from your provider attached to it:

```php
use Backstage\Mails\Laravel\Models\Mail;

$mail = Mail::latest()->first();

$mail->subject;      // 'Your order has shipped'
$mail->to;           // ['customer@example.com' => 'Customer']
$mail->status;       // 'Delivered', 'Hard Bounced', 'Opened', ...
$mail->opens;        // 3
$mail->delivered_at; // Carbon instance, or null
$mail->events;       // all events received for this mail, newest first
$mail->attachments;  // the attachments that were sent along
```

A set of scopes is available to query them:

```php
Mail::sent()->count();
Mail::unsent()->count();
Mail::delivered()->count();
Mail::opened()->count();
Mail::clicked()->count();
Mail::complained()->count();
Mail::bounced()->count();      // soft and hard bounces
Mail::softBounced()->count();
Mail::hardBounced()->count();
Mail::resent()->count();
```

### Relate emails to Eloquent models

Emails are often about something: an order confirmation belongs to an `Order`, a password reset to a `User`. You can attach any Eloquent model to a mail, so you can later ask a model which emails were sent about it.

First, let the model implement the `HasAssociatedMails` contract and use the `HasMails` trait:

```php
use Backstage\Mails\Laravel\Contracts\HasAssociatedMails;
use Backstage\Mails\Laravel\Traits\HasMails;
use Illuminate\Database\Eloquent\Model;

class Order extends Model implements HasAssociatedMails
{
    use HasMails;
}
```

Then use the `AssociatesModels` trait in your mailable and associate the models you want:

```php
use Backstage\Mails\Laravel\Traits\AssociatesModels;
use Illuminate\Mail\Mailable;

class OrderShipped extends Mailable
{
    use AssociatesModels;

    public function __construct(public Order $order)
    {
        $this->associateWith($order);
    }
}
```

`associateWith()` takes a single model, an array of models or a collection. The models are passed along in an encrypted header and linked to the logged mail while it is being sent, so nothing is exposed to your email provider.

Because the link is made through the tracking uuid, associating only works for mailers whose transport is one of the supported providers, with tracking enabled in `mails.logging.tracking`.

Afterwards you can read the emails and their events straight off the model:

```php
$order->mails;   // every mail sent about this order
$order->events;  // every event received for those mails

$order->mails()->hardBounced()->exists();
```

You can also link a mail to a model yourself, for example when you did not send it through a mailable:

```php
$order->associateMail($mail);
```

### Resend a logged email

Because the package stores the rendered content of every email, a logged email can be sent again — to the original recipient, or to somebody else entirely. This is useful when a customer says an email never arrived.

The quickest way is the artisan command, which takes the uuid of the logged mail:

```bash
php artisan mail:resend 9a3f8e1c-...
```

The command is interactive and asks for every recipient you did not pass, so you can accept the original recipients by leaving a prompt empty. Pass all three to run it without any prompts:

```bash
php artisan mail:resend 9a3f8e1c-... other@example.com --cc=boss@example.com --bcc=archive@example.com
```

From your own code, use the `ResendMail` action:

```php
use Backstage\Mails\Laravel\Actions\ResendMail;
use Backstage\Mails\Laravel\Models\Mail;

$mail = Mail::where('uuid', $uuid)->first();

(new ResendMail)($mail, to: ['other@example.com']);
```

Resending is queued, and the original attachments are sent along. When it goes out, the `MailResent` event is dispatched and the mail's `resent_at` timestamp is updated, so a resent email is easy to recognise later.

### Get notified of important events such as bounces, high bounce rate or spam complaints

The whole point of logging emails is to hear about it when something goes wrong before your customer does. The package can notify you over `mail`, `discord`, `slack` and `telegram`.

First tell the package where to send notifications, per channel:

```php
'notifications' => [
    'mail' => [
        'to' => ['developers@example.com'],
    ],

    'discord' => [
        'to' => ['1234567890'],
    ],

    'slack' => [
        'to' => ['https://hooks.slack.com/services/...'],
    ],

    'telegram' => [
        'to' => ['1234567890'],
    ],
],
```

Every channel except `mail` needs its own notification channel package:

```bash
composer require laravel-notification-channels/discord
composer require laravel/slack-notification-channel
composer require laravel-notification-channels/telegram
```

Then pick which channels each event should notify. An empty array means no notification is sent:

```php
'events' => [
    // A mail hard bounced, so this address will never receive mail again
    'hard_bounced' => [
        'notify' => ['mail', 'discord'],
    ],

    // Somebody marked one of your mails as spam
    'complained' => [
        'notify' => ['mail'],
    ],

    // Too many of your mails bounce, which puts your sending reputation at risk
    'bouncerate' => [
        'notify' => ['mail'],

        'retain' => 30, // look at the mails of the last 30 days

        'treshold' => 1, // notify above 1%
    ],
],
```

Hard bounces and spam complaints are reported the moment the webhook comes in. The bounce rate is not tied to a single email, so it is checked by a command that you schedule yourself:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('mail:bounce-rate')->daily();
```

The command compares the bounce rate over the retained period against your threshold, notifies you when it is exceeded, and exits with a failure status so your scheduler can pick it up as well. You can always run it by hand:

```bash
php artisan mail:bounce-rate
```

### Prune logged emails

Logging every email means the table grows quickly, especially when you store the html of every message. The package therefore ships with a pruning command that removes old logged mails, including their events and attachment records:

```bash
php artisan mail:prune
```

How long mails are kept is configured in the config file:

```php
'database' => [
    'pruning' => [
        'enabled' => true,
        'after' => 30, // days
    ],
],
```

Pruning only happens when it is enabled; the command tells you when it is not. Attachment files that were copied to disk are not removed, so clean up that directory separately if you store attachments.

Schedule the command to keep the table slim without thinking about it:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('mail:prune')->daily();
```

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
