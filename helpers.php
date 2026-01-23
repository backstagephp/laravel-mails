<?php

namespace Backstage\Mails;

use Backstage\Mails\Shared\Terminal;

if (! function_exists('console')) {
    function console(): Terminal
    {
        return new Terminal;
    }
}
