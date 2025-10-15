<?php

namespace Backstage\Mails\Managers;

use Backstage\Mails\Drivers\MailgunDriver;
use Backstage\Mails\Drivers\PostmarkDriver;
use Backstage\Mails\Drivers\ResendDriver;
use Illuminate\Support\Manager;

class MailProviderManager extends Manager
{
    public function with($driver)
    {
        return $this->driver($driver);
    }

    protected function createPostmarkDriver(): PostmarkDriver
    {
        return new PostmarkDriver;
    }

    protected function createMailgunDriver(): MailgunDriver
    {
        return new MailgunDriver;
    }

    protected function createResendDriver(): ResendDriver
    {
        return new ResendDriver;
    }

    public function getDefaultDriver(): ?string
    {
        return null;
    }
}
