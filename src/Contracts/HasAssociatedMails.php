<?php

namespace Backstage\Mails\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface HasAssociatedMails
{
    public function mails(): MorphToMany;

    public function events(): HasManyThrough;

    /**
     * @param  Model|Model[]  $mail
     */
    public function associateMail($mail): static;
}
