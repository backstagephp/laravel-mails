<?php

namespace Backstage\Mails\Laravel\Contracts;

interface MailProviderContract
{
    public function driver(?string $driver = null);
}
