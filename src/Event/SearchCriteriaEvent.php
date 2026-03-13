<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Event;

use Efrogg\Synergy\Data\Criteria;
use Symfony\Component\HttpFoundation\Request;

final class SearchCriteriaEvent
{
    /**
     * @param class-string $entityClass
     */
    public function __construct(
        private readonly string $entityClass,
        private readonly Criteria $criteria,
        private readonly bool $isMain,
        private readonly ?Request $request = null,
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
}
