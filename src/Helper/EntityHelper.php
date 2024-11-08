<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Helper;

use Efrogg\Synergy\Entity\SynergyEntityInterface;
use Symfony\Component\Serializer\Attribute\Ignore;

class EntityHelper
{
    /**
     * @var array<class-string<SynergyEntityInterface>>
     */
    private array $entityClasses = [];

    /**
     * @var array<class-string<array<string,mixed>>>
     */
    #[Ignore]
    #[\Symfony\Component\Serializer\Annotation\Ignore]
    private array $_entityDefinitions = [];
    /**
     * @var array<string, string>
     */
    protected static array $entityNamesCache = [];

    public static function getEntityName(string $class): string
    {
        static::$entityNamesCache[$class] ??= (new \ReflectionClass($class))->getShortName();

        return static::$entityNamesCache[$class];
    }

    /**
     * @deprecated not uset for now... maybe later
     */
    public function setEntityDefinitions(array $_entityDefinitions): void
    {
        $this->_entityDefinitions = $_entityDefinitions;
    }

    public function getEntityDefinitions(): array
    {
        return $this->_entityDefinitions;
    }

    /**
     * @param iterable<SynergyEntityInterface> $entities
     */
    public function setEntities(iterable $entities): void
    {
        foreach ($entities as $entity) {
            $this->entityClasses[$entity::getEntityName()] = $entity::class;
        }
    }

    /**
     * @return array<class-string<SynergyEntityInterface>>
     */
    public function getEntityClasses(): array
    {
        return $this->entityClasses;
    }

    /**
     * @return class-string<SynergyEntityInterface>|null
     */
    public function findEntityClass(string $entityName): ?string
    {
        return $this->entityClasses[$entityName] ?? null;
    }

    /**
     * @return array<string>
     */
    public function getEntityNames(): array
    {
        return array_keys($this->entityClasses);
    }

    public function findEntityName(?string $className): ?string
    {
        foreach ($this->entityClasses as $entityName => $entityClass) {
            if ($className === $entityClass) {
                return $entityName;
            }
        }

        return null;
    }
}
