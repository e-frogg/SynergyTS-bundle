<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Mercure;

use Efrogg\Synergy\Entity\SynergyEntityInterface;

abstract class EntityAction implements \Countable
{
    protected static string $action;
    /** @var array<string,mixed> */
    protected static array $additionalParameters = [];

    /**
     * @param array<SynergyEntityInterface> $entities
     */
    public function __construct(
        protected array $entities = [],
    ) {
    }

    public static function getAction(): string
    {
        return static::$action ?? throw new \LogicException('Action not defined');
    }

    /**
     * @return array<SynergyEntityInterface>
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    public function count(): int
    {
        return count($this->entities);
    }

    /**
     * @param array<SynergyEntityInterface> $entities
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

    public function isSame(EntityAction $action): bool
    {
        return $action::getAction() === static::getAction();
    }

    public function merge(EntityAction $action): void
    {
        foreach ($action->getEntities() as $newEntity) {
            if (in_array($newEntity, $this->entities, true)) {
                continue;
            }
            $this->entities[] = $newEntity;
        }
    }
}
