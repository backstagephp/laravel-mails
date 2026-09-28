<?php

namespace Backstage\Mails\Laravel\Drivers;

use Backstage\Mails\Laravel\Enums\SendFailure;
use Backstage\Mails\Laravel\Exceptions\LaravelMailException;
use Backstage\Mails\Laravel\Models\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

abstract class MailDriver
{
    protected string $mailModel;

    protected string $mailEventModel;

    protected string $uuidHeaderName;

    public function __construct()
    {
        $this->mailModel = config('mails.models.mail');
        $this->mailEventModel = config('mails.models.event');
        $this->uuidHeaderName = config('mails.headers.uuid');
    }

    abstract protected function getUuidFromPayload(array $payload): ?string;

    abstract protected function dataMapping(): array;

    abstract protected function getTimestampFromPayload(array $payload): string;

    abstract protected function eventMapping(): array;

    public function getMailFromPayload(array $payload): ?Mail
    {
        // Without a uuid the payload cannot be traced back to a single mail, so
        // matching on it would attribute the event to an unrelated recipient.
        if (! $uuid = $this->getUuidFromPayload($payload)) {
            return null;
        }

        return $this->mailModel::query()
            ->firstWhere('uuid', $uuid);
    }

    public function getDataFromPayload(array $payload): array
    {
        return collect($this->dataMapping())
            ->mapWithKeys(fn ($value, $key): array => [
                $key => is_array($v = data_get($payload, $value)) ? json_encode($v) : $v,
            ])
            ->map(fn ($value, $key) => $key === 'country_code' ? $this->normalizeCountryCode($value) : $value)
            ->filter()
            ->merge([
                'payload' => $payload,
                'type' => $this->getEventFromPayload($payload),
                'occurred_at' => $this->getTimestampFromPayload($payload),
            ])
            ->toArray();
    }

    /**
     * Providers do not always send an ISO 3166-1 alpha-2 code: Mailgun sends
     * "Unknown" when it cannot geolocate a recipient, which does not fit the
     * two character country_code column. Anything but a code is dropped.
     */
    protected function normalizeCountryCode(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $code = strtoupper(trim($value));

        return preg_match('/^[A-Z]{2}$/', $code) === 1 ? $code : null;
    }

    public function getEventFromPayload(array $payload): string
    {
        foreach ($this->eventMapping() as $event => $mapping) {
            if (collect($mapping)->every(fn ($value, $key): bool => data_get($payload, $key) === $value)) {
                return $event;
            }
        }

        throw LaravelMailException::unknownEventType();
    }

    public function logMailEvent(array $payload): void
    {
        $mail = $this->getMailFromPayload($payload);

        if (is_null($mail)) {
            return;
        }

        $data = $this->getDataFromPayload($payload);
        $method = Str::camel($data['type']);

        if (method_exists($this, $method)) {
            // log mail event
            $mail->events()->create($data);

            // update mail record with timestamp
            $this->{$method}($mail, $this->getTimestampFromPayload($payload));
        }
    }

    /**
     * Classify a failed send by matching the provider's error code against
     * the driver's sendFailureMapping(). Anything unmapped is transient, so
     * it is retried as before.
     */
    public function getSendFailure(TransportExceptionInterface $exception): SendFailure
    {
        $code = $this->getErrorCodeFromException($exception);

        if (is_null($code)) {
            return SendFailure::TRANSIENT;
        }

        foreach ($this->sendFailureMapping() as $failure => $codes) {
            if (in_array($code, $codes, true)) {
                return SendFailure::from($failure);
            }
        }

        return SendFailure::TRANSIENT;
    }

    /**
     * Provider error codes per SendFailure, e.g.
     * [SendFailure::PERMANENT->value => [300, 400]].
     */
    public function sendFailureMapping(): array
    {
        return [];
    }

    /**
     * Symfony's HTTP API transports (Postmark, Mailgun) end their error
     * messages in "(code <code>)."; drivers with another format override this.
     */
    protected function getErrorCodeFromException(TransportExceptionInterface $exception): int|string|null
    {
        return preg_match('/\(code (\d+)\)\.?$/', $exception->getMessage(), $matches)
            ? (int) $matches[1]
            : null;
    }

    public function accepted(Mail $mail, string $timestamp): void
    {
        $mail->update([
            'accepted_at' => $timestamp,
        ]);
    }

    public function clicked(Mail $mail, string $timestamp): void
    {
        $mail->update([
            'last_clicked_at' => $timestamp,
            'clicks' => $mail->clicks + 1,
        ]);
    }

    public function complained(Mail $mail, string $timestamp): void
    {
        $mail->update([
            'complained_at' => $timestamp,
        ]);
    }

    public function delivered(Mail $mail, string $timestamp): void
    {
        $mail->update([
            'delivered_at' => $timestamp,
        ]);
    }

    public function hardBounced(Mail $mail, string $timestamp): void
    {
        $mail->update([
            'hard_bounced_at' => $timestamp,
        ]);
    }

    public function softBounced(Mail $mail, string $timestamp): void
    {
        $mail->update([
            'soft_bounced_at' => $timestamp,
        ]);
    }

    public function opened(Mail $mail, string $timestamp): void
    {
        $mail->update([
            'last_opened_at' => $timestamp,
            'opens' => $mail->opens + 1,
        ]);
    }

    public function unsubscribed(Mail $mail, string $timestamp): void
    {
        $mail->update([
            'unsubscribed_at' => $timestamp,
        ]);
    }
}
