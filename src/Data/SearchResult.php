<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Data;

use Efrogg\Synergy\Entity\SynergyEntityInterface;

readonly class SearchResult
{
    /**
     * @param array<SynergyEntityInterface>     $entities
     * @param array<string,array<int|string>> $mainIds
     */
    public function __construct(
        private array $entities,
        private array $mainIds = []
    ) {
    }

    /**
     * @return array<SynergyEntityInterface>
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    /**
     * @return array<string,array<int|string>>
     */
    public function getMainIds(): array
    {
        return $this->mainIds;
    }
}
