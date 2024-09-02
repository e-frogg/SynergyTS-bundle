<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Data;

use Doctrine\ORM\QueryBuilder;

class Criteria
{
    /**
     * Finds entities by a set of criteria.
     *
     * @param array<string,mixed>       $filters
     * @param array<string,string>|null $orderBy
     * @param int|null                  $limit
     * @param int|null                  $offset
     *
     * @param array<string,Criteria>    $associations
     */
    public function __construct(
        private array $filters = [],
        private ?array $orderBy = null,
        private ?int $limit = null,
        private ?int $offset = null,
        private array $associations = [],
        private ?QueryBuilder $queryBuilder = null
    ) {
    }

    /**
     * @return QueryBuilder|null
     */
    public function getQueryBuilder(): ?QueryBuilder
    {
        return $this->queryBuilder;
    }

    /**
     * @param QueryBuilder|null $queryBuilder
     */
    public function setQueryBuilder(?QueryBuilder $queryBuilder): void
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @return Criteria[]
     */
    public function getAssociations(): array
    {
        return $this->associations;
    }

    public function addAssociation(string $propertyPath, ?Criteria $criteria = null): static
    {
        $criteria ??= new Criteria();
        $path = explode('.', $propertyPath);
        $propertyName = array_shift($path);
        $this->associations[$propertyName] = $criteria;
        while ($propertyName = array_shift($path)) {
            $criteria = $criteria->getAssociation($propertyName);
        }

        return $this;
    }

    public function getAssociation(string $propertyName): Criteria
    {
        if (!isset($this->associations[$propertyName])) {
            $this->addAssociation($propertyName);
        }

        return $this->associations[$propertyName];
    }

    public function addFilter(string $key, mixed $filter): static
    {
        $this->filters[$key] = $filter;
        return $this;
    }


    /**
     * @return mixed[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return array<string,string>|null
     */
    public function getOrderBy(): ?array
    {
        return $this->orderBy;
    }

    /**
     * @param array<string>|null $orderBy
     */
    public function setOrderBy(?array $orderBy): static
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function setOffset(?int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }


}
