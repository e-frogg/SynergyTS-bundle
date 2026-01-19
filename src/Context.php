<?php

declare(strict_types=1);

namespace Efrogg\Synergy;

class Context
{
    public static function createDefaultContext(): Context
    {
        return new self();
    }
}
