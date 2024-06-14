<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Mercure;

use Efrogg\Synergy\Entity\SynergyEntityInterface;

abstract class EntityAction
{
    protected static string $action;
    /** @var array<string,mixed> */
    protected static array $additionalParameters = [];

    /**
     * @param array<SynergyEntityInterface> $entities
     */
    public function __construct(
        protected array $entities = []
    ) {
    }

    public static function getAction(): string
    {
        return static::$action??throw new \LogicException('Action not defined');
    }

    /**
     * @return array<SynergyEntityInterface>
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    /**
     * @param array<SynergyEntityInterface> $entities
     *
     * @return static
     */
    public function setEntities(array $entities): static
    {
        $this->entities = $entities;
        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    public static function getAdditionalParameters(): array
    {
        return static::$additionalParameters;
    }
}
