<?php

namespace Backstage\Mails\Shared;

trait AsAction
{
    public function __invoke(...$parameters): mixed
    {
        // @phpstan-ignore-next-line
        return $this->handle(...$parameters);
    }
}
