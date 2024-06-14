<?php

namespace Efrogg\Synergy\Entity;

use Efrogg\Synergy\Helper\EntityHelper;

trait SynergyEntityTrait
{
    public static function getEntityName(): string {
        return EntityHelper::getEntityName(static::class);
    }
}
