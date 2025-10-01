<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Acl;

class AclContext
{
    private bool $systemContext = true;

    public function setSystemContext(bool $systemContext): void
    {
        $this->systemContext = $systemContext;
    }

    public function isSystemContext(): bool
    {
        return $this->systemContext;
    }
}
