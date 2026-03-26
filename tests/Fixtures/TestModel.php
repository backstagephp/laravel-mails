<?php

namespace Backstage\Mails\Laravel\Tests\Fixtures;

use Backstage\Mails\Laravel\Contracts\HasAssociatedMails;
use Backstage\Mails\Laravel\Traits\HasMails;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class TestModel extends Model implements HasAssociatedMails
{
    use HasMails, Notifiable;

    protected $table = 'test_models';

    protected $guarded = [];
}
