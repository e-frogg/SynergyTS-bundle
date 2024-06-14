<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Mapping;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class SynergyEntity
{
    public function __construct(
        public string $name = '',
        public string $description = '',
    ) {
    }


}
