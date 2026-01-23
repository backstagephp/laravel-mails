<?php

namespace Backstage\Mails\Exceptions;

use Exception;

class LaravelMailException extends Exception
{
    public static function unknownEventType(): self
    {
        return new self('Unknown event type. Please check the event mapping in your mail driver.');
    }
}
