<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Event;

use Efrogg\Synergy\Data\Criteria;
use Efrogg\Synergy\Data\SearchSelection;
use Symfony\Component\HttpFoundation\Request;

final class SearchSelectionEvent
{
    /**
     * @param class-string $entityClass
     */
    public function __construct(
        private readonly string $entityClass,
        private readonly Criteria $criteria,
        private readonly bool $isMain,
        private readonly ?Request $request = null,
        private ?SearchSelection $selection = null,
    ) {
    }

    /**
     * @return class-string
     */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function getCriteria(): Criteria
    {
        return $this->criteria;
    }

    public function isMain(): bool
    {
        return $this->isMain;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function setSelection(SearchSelection $selection): void
    {
        $this->selection = $selection;
    }

    public function getSelection(): ?SearchSelection
    {
        return $this->selection;
    }
}
