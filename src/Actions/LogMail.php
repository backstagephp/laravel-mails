<?php

namespace Backstage\Mails\Actions;

use Backstage\Mails\Enums\Provider;
use Backstage\Mails\Models\Mail;
use Backstage\Mails\Shared\AsAction;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mime\Address;

class LogMail
{
    use AsAction;

    public function handle(MessageSending|MessageSent $event): mixed
    {
        if (! config('mails.logging.enabled')) {
            return null;
        }

        $mail = $this->newMailModelInstance();

        if ($event instanceof MessageSending) {
            $mail->fill($this->getOnlyConfiguredAttributes($event));
            $mail->save();

            $this->collectAttachments($mail, $event->message->getAttachments());
        }

        if ($event instanceof MessageSent) {
            $mail = $mail->firstWhere('uuid', $this->getCustomUuid($event));

            $mail->update($this->getOnlyConfiguredAttributes($event));
        }

        return null;
    }

    /**
     * @return Mail
     */
    public function newMailModelInstance()
    {
        $model = config('mails.models.mail');

        return new $model;
    }

    public function getOnlyConfiguredAttributes(MessageSending|MessageSent $event): array
    {
        return collect($this->getDefaultLogAttributes($event))
            ->only($this->getConfiguredAttributes())
            ->merge($this->getMandatoryAttributes($event))
            ->merge([
                'mailer' => $event->data['mailer'],
                'transport' => config('mail.mailers.'.$event->data['mailer'].'.transport'),
            ])
            ->toArray();
    }

    public function getConfiguredAttributes(): array
    {
        return (array) config('mails.logging.attributes');
    }

    public function getDefaultLogAttributes(MessageSending|MessageSent $event): array
    {
        return [
            'subject' => $event->message->getSubject(),
            'from' => $this->getAddressesValue($event->message->getFrom()),
            'reply_to' => $this->getAddressesValue($event->message->getReplyTo()),
            'to' => $this->getAddressesValue($event->message->getTo()),
            'cc' => $this->getAddressesValue($event->message->getCc()),
            'bcc' => $this->getAddressesValue($event->message->getBcc()),
            'html' => $event->message->getHtmlBody(),
            'text' => $event->message->getTextBody(),
            'tags' => collect($event->message->getHeaders()->all('X-tag'))->map(fn ($tag) => $tag->getValue())->toArray(),
        ];
    }

    protected function getStreamId(MessageSending|MessageSent $event): ?string
    {
        if ($event->data['mailer'] !== Provider::POSTMARK) {
            return null;
        }

        if (null !== $messageStream = $event->message->getHeaders()->get('x-pm-message-stream')) {
            return $messageStream->getBody();
        }

        return config('mail.mailers.postmark.message_stream_id', 'outbound');
    }

    public function getMandatoryAttributes(MessageSending|MessageSent $event): array
    {
        return [
            'uuid' => $this->getCustomUuid($event),
            // 'mail_class' => $this->getMailClassHeaderValue($event),
            'sent_at' => $event instanceof MessageSent ? now() : null,
            'mailer' => $event->data['mailer'],
            'stream_id' => $this->getStreamId($event),
        ];
    }

    protected function getCustomUuid(MessageSending|MessageSent $event): ?string
    {
        if (! $event->message->getHeaders()->has(config('mails.headers.uuid'))) {
            return null;
        }

        $headerValue = $event->message->getHeaders()->get(config('mails.headers.uuid'));

        if (is_null($headerValue)) {
            return null;
        }

        return $headerValue->getBodyAsString();
    }

    protected function getAddressesValue(array $address): ?Collection
    {
        $addresses = collect($address)
            ->flatMap(fn (Address $address): array => [$address->getAddress() => $address->getName() === '' ? null : $address->getName()]);

        return $addresses->count() > 0 ? $addresses : null;
    }

    public function collectAttachments($mail, $attachments): void
    {
        collect($attachments)->each(function ($attachment) use ($mail): void {
            $attachmentModel = $mail->attachments()->create([
                'disk' => config('mails.logging.attachments.disk'),
                'uuid' => $attachment->getContentId(),
                'filename' => $attachment->getFilename(),
                'mime' => $attachment->getContentType(),
                'inline' => ! str_contains((string) $attachment->getFilename() !== '' && (string) $attachment->getFilename() !== '0' ? (string) $attachment->getFilename() : '', '.'), // workaround, because disposition is a private property
                'size' => strlen((string) $attachment->getBody()),
            ]);

            $this->saveAttachment($attachmentModel, $attachment);
        });
    }

    public function saveAttachment($attachmentModel, $attachment): void
    {
        Storage::disk($attachmentModel->disk)
            ->put($attachmentModel->storagePath, $attachment->getBody(), 'private');
    }
}
