<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Efrogg\Synergy\Exception\SerializerException;
use Efrogg\Synergy\Mapping\WriteProtected;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;

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
     * @param class-string<SynergyEntityInterface> $entityClass
     *
     * @throws SerializerException
     *
     * @see EntitySerializer
     */
    public function createOrEdit(string $entityClass, ParameterBag $body, ?SynergyEntityInterface $entity = null): mixed
    {
        $entity ??= new $entityClass();

        return $this->enrich($entity, $body);
    }

    /**
     * @throws SerializerException
     *
     * @see EntitySerializer
     */
    public function enrich(SynergyEntityInterface $entity, ParameterBag $body): mixed
    {
        $entityClass = $entity::class;

        $classMetaData = $this->classMetadataFactory->getMetadataFor($entity);
        $reflexionClass = $classMetaData->getReflectionClass();

        foreach ($body as $property => $value) {
            $realKey = $property;
            $setter = 'set'.ucfirst($property);
            if (str_ends_with($property, 'Id')) {
                // on a une relation
                if (null === $value || '' === $value) {
                    $value = null;
                } else {
                    $realKey = substr($property, 0, -2);
                    $setter = 'set'.ucfirst($realKey);
                    $type = $this->propertyTypeExtractor->getType($entityClass, $realKey);

                    if ($type instanceof NullableType) {
                        foreach ($type->getTypes() as $innerType) {
                            if ($innerType instanceof ObjectType) {
                                $type = $innerType;
                                break;
                            }
                        }
                    }

                    if ($type instanceof ObjectType) {
                        $className = $type->getClassName();
                        if (is_a($className, SynergyEntityInterface::class, true)) {
                            // on a une relation
                            $value = $this->entityManager
                                ->getRepository($className)
                                ->find($value);
                        }
                    }
                }
            } else {
                $type = $this->propertyTypeExtractor->getType($entityClass, $property);
                if ($type instanceof ObjectType) {
                    $className = $type->getClassName();
                    $value = $this->convertObjectValue($className, $value);
                }
            }

            try {
                $this->checkAttributesForWrite($reflexionClass->getProperty($realKey)->getAttributes());
            } catch (\ReflectionException $e) {
                if ($this->strictPropertyForWrite) {
                    throw new SerializerException('unknown property '.$realKey);
                }
            } catch (\Exception $e) {
                throw new SerializerException($realKey.' : '.$e->getMessage(), $e->getCode(), $e);
            }

            if (method_exists($entity, $setter)) {
                $entity->$setter($value);
            }
        }

        return $entity;
    }

    /**
     * @return \DateTimeImmutable|mixed
     *
     * @throws SerializerException
     */
    private function convertObjectValue(?string $className, mixed $value): mixed
    {
        if (null === $className) {
            throw new SerializerException('Object serializaion is not yet implemented');
            //            return (object)$value;
        }
        if (is_a($className, \DateTimeInterface::class, true)) {
            $value = new \DateTimeImmutable($value);
        }

        return $value;
    }

    /**
     * @param array<\ReflectionAttribute<object>> $reflectionAttributes
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
