<?php

declare(strict_types=1);

namespace Efrogg\Synergy\EventListener;

use Efrogg\Synergy\Entity\SynergyEntityInterface;

class EntityChangesetHolder
{
    /**
     * @var array<int,array<string, array<int,mixed>>>
     */
    private array $changesets = [];

    /**
     * @return array<array<string, array<int,mixed>>>
     */
    public function getChangesets(): array
    {
        return $this->changesets;
    }

    /**
     * @param array<string, array<int,mixed>> $changeset
     */
    public function setChangeset(SynergyEntityInterface $entity, array $changeset): void
    {
        $this->changesets[spl_object_id($entity)] = $changeset;
    }

    public function reset(): void
    {
        $this->changesets = [];
    }

    public function getChangeset(SynergyEntityInterface $entity): ?EntityChangeset
    {
        $objectId = spl_object_id($entity);
        $changeset = $this->changesets[$objectId] ?? null;
        if (null === $changeset) {
            return null;
        }

        return new EntityChangeset($changeset);
    }
}
