<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Efrogg\Synergy\Exception\SerializerException;
use Efrogg\Synergy\Mapping\WriteProtected;
use Exception;
use ReflectionAttribute;
use ReflectionException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

class SynergyEnricher
{

    protected bool $strictPropertyForWrite = false;

    public function __construct(
        protected readonly ClassMetadataFactoryInterface $classMetadataFactory,
        protected readonly PropertyTypeExtractorInterface $propertyTypeExtractor,
        protected readonly PropertyAccessorInterface $propertyAccessor,
        private readonly EntityManagerInterface $entityManager,

    ) {
    }

    /**
     * @param ?SynergyEntityInterface                              $entity
     * @param class-string<SynergyEntityInterface> $entityClass
     * @param ParameterBag                       $body
     *
     * @return mixed
     * @throws SerializerException
     * @see EntitySerializer
     */
    public function createOrEdit(string $entityClass, ParameterBag $body, ?SynergyEntityInterface $entity = null): mixed
    {
        $entity ??= new $entityClass();
        return $this->enrich($entity, $body);
    }

    /**
     * @param SynergyEntityInterface        $entity
     * @param ParameterBag $body
     *
     * @return mixed
     * @throws SerializerException
     * @see EntitySerializer
     */
    public function enrich(SynergyEntityInterface $entity, ParameterBag $body): mixed
    {
        $entityClass = $entity::class;

        $classMetaData = $this->classMetadataFactory->getMetadataFor($entity);
        $reflexionClass = $classMetaData->getReflectionClass();

        foreach ($body as $property => $value) {
            $realKey = $property;
            $setter = 'set' . ucfirst($property);
            if (str_ends_with($property, 'Id')) {
                // on a une relation
                if (null === $value || $value === '') {
                    $value = null;
                } else {
                    $realKey = substr($property, 0, -2);
                    $setter = 'set' . ucfirst($realKey);
                    $types = $this->propertyTypeExtractor->getTypes($entityClass, $realKey) ?? [];
                    foreach ($types as $type) {
                        $className = $type->getClassName();
                        if ($className === null) {
                            continue;
                        }
                        if (is_a($className, SynergyEntityInterface::class, true)) {
                            // on a une relation
                            $value = $this->entityManager
                                ->getRepository($className)
                                ->find($value);
                            // on a trouvé
                            break;
                        }
                    }
                }
            } else {
                /** @var Type[] $types */
                $types = $this->propertyTypeExtractor->getTypes($entityClass, $property) ?? [];

                foreach ($types as $type) {
                    $builtInType = $type->getBuiltinType();
                    $className = $type->getClassName();
                    if ($builtInType === 'object') {
                        $value = $this->convertObjectValue($className, $value);
                    }
                }
            }

            try {
                $this->checkAttributesForWrite($reflexionClass->getProperty($realKey)->getAttributes());
            } catch (ReflectionException $e) {
                if ($this->strictPropertyForWrite) {
                    throw new SerializerException('unknown property ' . $realKey);
                }
            } catch (Exception $e) {
                throw new SerializerException($realKey . ' : ' . $e->getMessage(), $e->getCode(), $e);
            }

            if (method_exists($entity, $setter)) {
                $entity->$setter($value);
            }
        }
        return $entity;
    }

    /**
     * @param ?string $className
     * @param mixed  $value
     *
     * @return DateTimeImmutable|mixed
     * @throws SerializerException
     */
    private function convertObjectValue(?string $className, mixed $value): mixed
    {
        if (null === $className) {
            throw new SerializerException('Object serializaion is not yet implemented');
//            return (object)$value;
        }
        if (is_a($className, DateTimeInterface::class, true)) {
            $value = new DateTimeImmutable($value);
        }
        return $value;
    }

    /**
     * @param array<ReflectionAttribute<object>> $reflectionAttributes
     *
     * @return void
     */
    private function checkAttributesForWrite(array $reflectionAttributes): void
    {
        foreach ($reflectionAttributes as $reflectionAttribute) {
            if (is_a($reflectionAttribute->getName(), WriteProtected::class, true)) {
                throw new SerializerException('write protected');
            }
        }
    }
}
