<?php

namespace Backstage\Mails\Traits;

use Backstage\Mails\Models\Mail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @mixin Model
 */
trait HasMails
{
    public function mails(): MorphToMany
    {
        return $this->morphToMany(config('mails.models.mail'), 'mailable', 'mailables', 'mailable_id', 'mail_id');
    }

    public function events(): HasManyThrough
    {
        return $this->hasManyThrough(config('mails.models.event'), config('mails.models.mail'));
    }

    /**
     * @param  Mail|Mail[]  $mail
     */
    public function associateMail($mail): static
    {
        $this->mails()->syncWithoutDetaching($mail);

        return $this;
    }
}
