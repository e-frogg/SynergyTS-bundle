<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Data;

final readonly class SearchSelection
{
    /**
     * @param list<int|string> $orderedIds
     */
    public function __construct(
        private array $orderedIds,
        private ?int $totalCount = null,
        private ?Criteria $hydrationCriteria = null,
    ) {
    }

    /**
     * @return list<int|string>
     */
    public function getOrderedIds(): array
    {
        return $this->orderedIds;
    }

    public function getTotalCount(): ?int
    {
        return $this->totalCount;
    }

    public function getHydrationCriteria(): Criteria
    {
        if ($this->hydrationCriteria instanceof Criteria) {
            return $this->hydrationCriteria;
        }

        return new Criteria()->setIds($this->orderedIds);
    }
}
