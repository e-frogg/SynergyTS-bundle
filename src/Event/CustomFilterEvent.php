<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Event;

use Doctrine\ORM\QueryBuilder;

class CustomFilterEvent
{
    private \Stringable|string|null $expr = null;

    private bool $handled = false;

    /**
     * @param array<mixed> $filterData
     */
    public function __construct(
        public string $filterType,
        public string $field,
        public array $filterData,
        public QueryBuilder $qb,
        public string $alias,
        public string $paramName
    ) {
    }

    public function getExpr(): \Stringable|string|null
    {
        return $this->expr;
    }

    public function setExpr(\Stringable|string|null $expr): void
    {
        $this->expr = $expr;
    }

    public function setHandled(bool $handled = true): void
    {
        $this->handled = $handled;
    }

    public function isHandled(): bool
    {
        return $this->handled;
    }
}
