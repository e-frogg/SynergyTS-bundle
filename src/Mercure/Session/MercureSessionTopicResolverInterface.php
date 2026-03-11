<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Mercure\Session;

use Efrogg\Synergy\Entity\SynergyEntityInterface;

interface MercureSessionTopicResolverInterface
{
    /**
     * @return array<string>
     */
    public function findTopicsForEntity(SynergyEntityInterface $entity): array;
}
