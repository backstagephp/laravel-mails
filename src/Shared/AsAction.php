<?php

namespace Backstage\Mails\Shared;

trait AsAction
{
    public function __invoke(...$parameters): mixed
    {
        return $this->handle(...$parameters);
    }
}
