<?php

namespace Backstage\Mails\Traits;

use Backstage\Mails\Contracts\HasAssociatedMails;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;

/**
 * @mixin Mailable
 *
 * @phpstan-ignore-next-line
 */
trait AssociatesModels
{
    public function associateWith($model): void
    {
        if ($model instanceof Collection) {
            $model = $model->all();
        } elseif (! is_array($model)) {
            $model = Arr::wrap($model);
        }

        $this->associateMany($model);
    }

    /**
     * @param  array<Model&HasAssociatedMails>  $models
     */
    public function associateMany(array $models): void
    {
        $header = $this->getEncryptedAssociatedModelsHeader($models);

        $this->withSymfonyMessage(fn (Email $email): Headers => $email->getHeaders()->addTextHeader(
            config('mails.headers.associate'),
            $header,
        ));
    }

    /**
     * @param  array<Model>  $models
     */
    protected function getEncryptedAssociatedModelsHeader(array $models): string
    {
        $identifiers = [];

        foreach ($models as $model) {
            $identifiers[] = [$model::class, $model->getKeyname(), $model->getKey()];
        }

        $header = json_encode($identifiers);

        return encrypt($header);
    }
}
