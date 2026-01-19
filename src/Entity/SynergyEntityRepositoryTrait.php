<?php

namespace Efrogg\Synergy\Entity;

use Efrogg\Synergy\Helper\EntityHelper;

trait SynergyEntityRepositoryTrait
{
    private ?string $_entityName = null;

    public function getSynergyEntityName(): string {
        return $this->_entityName ??= EntityHelper::getEntityName($this->getEntityName());
    }
}
