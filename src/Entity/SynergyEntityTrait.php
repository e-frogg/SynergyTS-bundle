<?php

namespace Efrogg\Synergy\Entity;

use Efrogg\Synergy\Helper\EntityHelper;

trait SynergyEntityTrait
{
    private ?string $_entityName = null;

    public static function getEntityName(): string
    {
        return EntityHelper::getEntityName(static::class);
    }

    public function setEntityName(?string $entityName): void
    {
        $this->_entityName = $entityName;
    }
}
