<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Entity;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('synergy.entity')]
interface SynergyEntityInterface
{
    public function getId(): null|string|int;

    public function setId(string|int $id): static;

    public static function getEntityName(): string;
}
