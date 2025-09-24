<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Entity;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @template T of SynergyEntityInterface
 * @extends ServiceEntityRepository<T>
 */
abstract class SynergyEntityRepository extends ServiceEntityRepository implements SynergyEntityRepositoryInterface
{
    public function getSynergyEntityName(): string
    {
        return $this->getEntityName();
    }

}
