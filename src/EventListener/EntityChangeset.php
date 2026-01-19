<?php

declare(strict_types=1);

namespace Efrogg\Synergy\EventListener;

readonly class EntityChangeset
{
    /**
     * @param array<string,array<int,mixed>> $changesets
     */
    public function __construct(
        private array $changesets = []
    ) {
    }

    public function hasChanged(string $property): bool
    {
        return isset($this->changesets[$property]);
    }

    public function getBefore(string $property): mixed
    {
        return $this->changesets[$property][0] ?? null;
    }

    public function getAfter(string $property): mixed
    {
        return $this->changesets[$property][1] ?? null;
    }
}
