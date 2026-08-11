<?php

namespace Backstage\Mails\Laravel\Drivers;

use Illuminate\Mail\Events\MessageSending;

class SesV2Driver extends SesDriver
{
    /**
     * The X-SES-CONFIGURATION-SET header is only documented for the v1
     * SendRawEmail API, so mailers on the ses-v2 transport must carry the
     * configuration set themselves through
     * mail.mailers.<mailer>.options.ConfigurationSetName.
     */
    protected function shouldAttachConfigurationSet(MessageSending $event): bool
    {
        return false;
    }
}
