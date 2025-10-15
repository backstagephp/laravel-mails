<?php

namespace Backstage\Mails\Actions;

use Backstage\Mails\Jobs\ResendMailJob;
use Backstage\Mails\Models\Mail as Mailable;
use Backstage\Mails\Shared\AsAction;

class ResendMail
{
    use AsAction;

    public function handle(Mailable $mailable, array $to = [], array $cc = [], array $bcc = [], array $replyTo = []): void
    {
        ResendMailJob::dispatch($mailable, $to, $cc, $bcc, $replyTo);
    }
}
