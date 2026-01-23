<?php

namespace Backstage\Mails\Listeners;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Email;

class StoreMailRelations
{
    public function handle(MessageSending $messageSending): void
    {
        $message = $messageSending->message;

        if (! $this->shouldAssociateModels($message)) {
            return;
        }

        $models = $this->getAssociatedModels($message);

        $mail = $this->getMailModel($message);

        foreach ($models as $identifier) {
            [$model, $keyName, $id] = $identifier;

            $model = $model::query()->where($keyName, $id)->limit(1)->first();

            $model->associateMail($mail);
        }
    }

    protected function shouldAssociateModels(Email $email): bool
    {
        return $email->getHeaders()->has(
            $this->getAssociatedHeaderName(),
        );
    }

    protected function getAssociatedModels(Email $email): array | false
    {
        $encrypted = $this->getHeaderBody(
            $email,
            $this->getAssociatedHeaderName(),
        );

        $payload = decrypt($encrypted);

        return json_decode((string) $payload, true);
    }

    protected function getMailModel(Email $email): Model
    {
        $uuid = $this->getHeaderBody($email, config('mails.headers.uuid'));

        $model = config('mails.models.mail');

        return $model::query()->where('uuid', $uuid)->limit(1)->first();
    }

    protected function getHeaderBody(Email $email, string $header): mixed
    {
        return $email->getHeaders()->getHeaderBody($header);
    }

    protected function getAssociatedHeaderName(): string
    {
        return config('mails.headers.associate', 'X-Mails-Associated-Models');
    }
}
