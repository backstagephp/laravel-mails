<?php

namespace Backstage\Mails\Laravel\Enums;

enum Provider: string
{
    case POSTMARK = 'postmark';
    case MAILGUN = 'mailgun';
    case RESEND = 'resend';
    case SES = 'ses';
    case SES_V2 = 'ses-v2';
}
