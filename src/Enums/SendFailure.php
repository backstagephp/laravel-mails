<?php

namespace Backstage\Mails\Laravel\Enums;

/**
 * Why a provider refused to send a mail, so the sender only retries when a
 * retry can actually succeed.
 */
enum SendFailure: string
{
    /** The provider suppresses the recipient: stop mailing it, don't retry. */
    case INACTIVE_RECIPIENT = 'inactive_recipient';

    /** The request fails the same way on every attempt: don't retry. */
    case PERMANENT = 'permanent';

    /** Rate limits, outages, timeouts and anything unknown: retry. */
    case TRANSIENT = 'transient';
}
