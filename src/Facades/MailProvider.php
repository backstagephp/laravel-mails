<?php

namespace Backstage\Mails\Laravel\Facades;

use Backstage\Mails\Laravel\Contracts\MailDriverContract;
use Backstage\Mails\Laravel\Contracts\MailProviderContract;
use Illuminate\Support\Facades\Facade;

/**
 * @method static MailDriverContract with(string $driver)
 */
class MailProvider extends Facade
{
    protected static function getFacadeAccessor()
    {
        return MailProviderContract::class;
    }
}
