<?php

namespace Backstage\Mails\Contracts;

interface MailProviderContract
{
    public function driver(?string $driver = null);
}
