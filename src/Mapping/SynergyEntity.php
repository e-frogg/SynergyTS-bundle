<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Mapping;

#[\Attribute(\Attribute::TARGET_CLASS)]
class SynergyEntity
{
    public const string ID_TYPE_NUMERIC = 'numeric';
    public const string ID_TYPE_STRING = 'string';

    public function __construct(
        public string $name = '',
        public string $description = '',
        public string $idType = self::ID_TYPE_NUMERIC
    ) {
    }
}
