<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Mercure;

class EntityRemoveAction extends EntityAction
{
    protected static string $action = 'remove';

    /**
     * @param array<int|string> $entityIds
     */
    public function __construct(array $entities = [], private array $entityIds = [])
    {
        parent::__construct($entities);
    }

    /**
     * @return array<int|string>
     */
    public function getEntityIds(): array
    {
        return $this->entityIds;
    }
}
