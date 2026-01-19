<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Helper;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityRepository;
use Efrogg\Synergy\Entity\SynergyEntityInterface;
use Efrogg\Synergy\Entity\SynergyEntityRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Contracts\Service\Attribute\Required;

class EntityHelper
{
    /**
     * @var array<string,class-string<SynergyEntityInterface>>
     */
    private array $entityClasses = [];

    /**
     * @var array<string,mixed>
     */
    #[Ignore]
    private array $_entityDefinitions = [];
    /**
     * @var array<string, string>
     */
    protected static array $entityNamesCache = [];

    /**
     * @param class-string<SynergyEntityInterface> $class
     *
     * @throws \ReflectionException
     */
    public static function getEntityName(string $class): string
    {
        static::$entityNamesCache[$class] ??= new \ReflectionClass($class)->getShortName();

        return static::$entityNamesCache[$class];
    }

    /**
     * @deprecated not used for now... maybe later
     *
     * @param array<string,mixed> $_entityDefinitions
     */
    public function setEntityDefinitions(array $_entityDefinitions): void
    {
        $this->_entityDefinitions = $_entityDefinitions;
    }

    /**
     * @deprecated not used for now... maybe later
     *
     * @return array<string,mixed>
     */
    public function getEntityDefinitions(): array
    {
        return $this->_entityDefinitions;
    }

    /**
     * @param iterable<SynergyEntityInterface> $entities
     */
    #[Required]
    public function setEntities(
        #[AutowireIterator('synergy.entity')]
        iterable $entities,
    ): void {
        foreach ($entities as $entity) {
            $this->entityClasses[$entity::getEntityName()] = $entity::class;
        }
    }

    /**
     * @param iterable<SynergyEntityRepositoryInterface> $repositories
     */
    #[Required]
    public function setEntityRepositories(
        #[AutowireIterator('synergy.entity-repository')]
        iterable $repositories,
    ): void {
        foreach ($repositories as $repository) {
            if(!$repository instanceof EntityRepository) {
                throw new \InvalidArgumentException('Entity repository must extend ServiceEntityRepository');
            }
            /** @var class-string<SynergyEntityInterface> $className */
            $className = $repository->getClassName();
            $this->entityClasses[$repository->getSynergyEntityName()] = $className;
        }
    }

    /**
     * @return array<class-string<SynergyEntityInterface & object>>
     */
    public function getEntityClasses(): array
    {
        return $this->entityClasses;
    }

    /**
     * @return class-string<SynergyEntityInterface & object>|null
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
