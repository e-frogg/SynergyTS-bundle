<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Mapping;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class SynergyFormField
{
    public function __construct(
        public bool $ignore = false,
    ) {
    }
}
