<?php

namespace Backstage\Mails\Enums;

enum Provider: string
{
    case POSTMARK = 'postmark';
    case MAILGUN = 'mailgun';
    case RESEND = 'resend';
}
