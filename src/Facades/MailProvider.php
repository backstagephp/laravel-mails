<?php

namespace Backstage\Mails\Facades;

use Backstage\Mails\Contracts\MailDriverContract;
use Backstage\Mails\Contracts\MailProviderContract;
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
