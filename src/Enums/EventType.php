<?php

namespace Backstage\Mails\Enums;

enum EventType: string
{
    case ACCEPTED = 'accepted';
    case CLICKED = 'clicked';
    case COMPLAINED = 'complained';
    case DELIVERED = 'delivered';
    case SOFT_BOUNCED = 'soft_bounced';
    case HARD_BOUNCED = 'hard_bounced';
    case OPENED = 'opened';
    case UNSUBSCRIBED = 'unsubscribed';

    // Others, Postmark specific
    case TRANSIENT = 'transient';
    case UNSUBSCRIBE = 'unsubscribe';
    case SUBSCRIBE = 'subscribe';
    case AUTO_RESPONDER = 'auto_responder';
    case ADDRESS_CHANGE = 'address_change';
    case DNS_ERROR = 'dns_error';
    case SPAM_NOTIFICATION = 'spam_notification';
    case OPEN_RELAY_TEST = 'open_relay_test';
    case SOFT_BOUNCE = 'soft_bounce';
    case VIRUS_NOTIFICATION = 'virus_notification';
    case CHALLENGE_VERIFICATION = 'challenge_verification';
    case BAD_EMAIL_ADDRESS = 'bad_email_address';
    case SPAM_COMPLAINT = 'spam_complaint';
    case MANUALLY_DEACTIVATED = 'manually_deactivated';
    case UNCONFIRMED = 'unconfirmed';
    case BLOCKED = 'blocked';
    case SMTP_API_ERROR = 'smtp_api_error';
    case INBOUND_ERROR = 'inbound_error';
    case DMARC_POLICY = 'dmarc_policy';
    case TEMPLATE_RENDERING_FAILED = 'template_rendering_failed';

    case UNKNOWN = 'unknown';
}
