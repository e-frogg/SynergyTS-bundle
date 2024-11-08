<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Entity;

interface SynergyEntityInterface
{
    public function getId(): string|int|null;

    public function setId(string|int $id): static;

    public static function getEntityName(): string;
}
