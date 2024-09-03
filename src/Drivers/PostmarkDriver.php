<?php

namespace Vormkracht10\Mails\Drivers;

use Vormkracht10\Mails\Contracts\MailDriverContract;
use Vormkracht10\Mails\Enums\Events\Mapping;
use Vormkracht10\Mails\Enums\Events\Postmark;
use Vormkracht10\Mails\Enums\WebhookEventType;
use Vormkracht10\Mails\Models\Mail;

class PostmarkDriver implements MailDriverContract
{
    protected $mailModel;

    protected $mailEventModel;

    public function __construct()
    {
        $this->mailModel = config('mails.models.mail');
        $this->mailEventModel = config('mails.models.event');
        $this->uuidHeaderName = config('mails.headers.uuid');
    }

    public function getUuidFromPayload(array $payload): ?string
    {
        return $payload['Metadata'][$this->uuidHeaderName] ??
            $payload['Metadata'][strtolower($this->uuidHeaderName)] ??
            $payload['Metadata'][strtoupper($this->uuidHeaderName)] ??
            null;
    }

    public function getMailFromPayload(array $payload): ?Mail
    {
        return $this->mailModel::query()
            ->firstWhere('uuid', $this->getUuidFromPayload($payload));
    }

    public function events(): array
    {
        return [
            Postmark::CLICK->value => Mapping::CLICK->value,
            Postmark::COMPLAINT->value => Mapping::COMPLAINT->value,
            Postmark::DELIVERY->value => Mapping::DELIVERY->value,
            Postmark::HARD_BOUNCE->value => Mapping::BOUNCE->value,
            Postmark::OPEN->value => Mapping::OPEN->value,
        ];
    }

    public function record(Mail $mail, WebhookEventType $type, array $payload, $timestamp = null): void
    {
        $timestamp ??= now();

        $method = strtolower($type->name);

        if (method_exists($this, $method)) {
            if (is_null($mail)) {
                return;
            }

            $this->{$method}($mail, $timestamp);

            $this->logEvent($mail, $type, $payload, $timestamp);
        }
    }

    public function logEvent(Mail $mail, WebhookEventType $event, array $payload, $timestamp): void
    {
        $mail->events()->create([
            'type' => $event,
            'ip_address' => $payload['Geo']['IP'] ?? null,
            'hostname' => isset($payload['Geo']['IP']) ? gethostbyaddr($payload['Geo']['IP']) : null,
            'platform' => $payload['Platform'] ?? null,
            'os' => $payload['OS']['Family'] ?? null,
            'browser' => $payload['Client']['Family'] ?? null,
            'user_agent' => $payload['UserAgent'] ?? null,
            'country_code' => $payload['Geo']['CountryISOCode'] ?? null,
            'city' => $payload['Geo']['City'] ?? null,
            'country_code' => $payload['OriginalLink'] ?? null,
            'tag' => $payload['Tag'] ?? null,
            'payload' => $payload,
            'occurred_at' => $timestamp,
        ]);
    }

    public function click($mail, $timestamp): void
    {
        $mail->update([
            'last_clicked_at' => $timestamp,
            'clicks' => $mail->clicks + 1,
        ]);
    }

    public function complaint($mail, $timestamp): void
    {
        $mail->update([
            'complained_at' => $timestamp,
        ]);
    }

    public function delivery($mail, $timestamp): void
    {
        $mail->update([
            'delivered_at' => $timestamp,
        ]);
    }

    public function bounce($mail, $timestamp): void
    {
        $mail->update([
            'hard_bounced_at' => $timestamp,
        ]);
    }

    public function open($mail, $timestamp): void
    {
        $mail->update([
            'last_opened_at' => $timestamp,
            'opens' => $mail->opens + 1,
        ]);
    }
}
