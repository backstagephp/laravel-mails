<?php

namespace Backstage\Mails\Laravel\Messages;

use Backstage\Mails\Laravel\Contracts\HasAssociatedMails;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;
use Symfony\Component\Mime\Email;

class AssociatedMailMessage extends MailMessage
{
    /**
     * @param  Model|Collection|array<Model&HasAssociatedMails>  $models
     */
    public function associateModels(Model|Collection|array $models): static
    {
        if ($models instanceof Collection) {
            $models = $models->all();
        } elseif ($models instanceof Model) {
            $models = [$models];
        }

        $identifiers = [];

        foreach ($models as $model) {
            $identifiers[] = [$model::class, $model->getKeyName(), $model->getKey()];
        }

        $this->withSymfonyMessage(function (Email $email) use ($identifiers) {
            $header = config('mails.headers.associate', 'X-Mails-Associated-Models');

            if ($email->getHeaders()->has($header)) {
                return;
            }

            $email->getHeaders()->addTextHeader(
                $header,
                encrypt(json_encode($identifiers)),
            );
        });

        return $this;
    }
}
