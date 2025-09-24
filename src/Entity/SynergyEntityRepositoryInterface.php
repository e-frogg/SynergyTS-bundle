<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Entity;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('synergy.entity-repository')]
interface SynergyEntityRepositoryInterface
{
    public function getSynergyEntityName(): string;

}
