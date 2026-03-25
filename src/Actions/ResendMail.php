<?php

namespace Backstage\Mails\Laravel\Actions;

use Backstage\Mails\Laravel\Jobs\ResendMailJob;
use Backstage\Mails\Laravel\Models\Mail as Mailable;
use Backstage\Mails\Laravel\Shared\AsAction;

class ResendMail
{
    use AsAction;

    public function handle(Mailable $mailable, array $to = [], array $cc = [], array $bcc = [], array $replyTo = []): void
    {
        ResendMailJob::dispatch($mailable, $to, $cc, $bcc, $replyTo);
    }
}
