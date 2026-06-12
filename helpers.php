<?php

namespace Backstage\Mails\Laravel;

use Backstage\Mails\Laravel\Shared\Terminal;

if (! function_exists('console')) {
    function console(): Terminal
    {
        return new Terminal;
    }
}
