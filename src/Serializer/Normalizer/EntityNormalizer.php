<?php

namespace Efrogg\Synergy\Serializer\Normalizer;

use Doctrine\Common\Collections\Collection;
use Efrogg\Synergy\Entity\SynergyEntityInterface;
use Efrogg\Synergy\Helper\TypeHelper;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\ObjectType;

class EntityNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public const int DISCOVER_NONE = 0;
    /**
     * discover oneToMany (ex : vehicle -> vehicleModel).
     */
    public const int DISCOVER_ASCENDING = 1;

    /**
     * discover manyToOne (ex : vehicleModel -> vehicles).
     */
    public const int DISCOVER_DESCENDING = 2;

    public const int DISCOVER_ALL = self::DISCOVER_ASCENDING | self::DISCOVER_DESCENDING;

    /**
     * @var array<string,SynergyEntityInterface>
     */
    private array $discoveredEntities = [];

    /**
     * activate entity discovery for relations (oneToMany, manyToOne).
     */
    private int $autoDiscover = self::DISCOVER_NONE;

    /** @var array<string> */
    protected const array SKIPPED_ATTRIBUTE = [
        'createdAt',
        'updatedAt',
        '_properties',
        '_entityName',
    ];

    /** @var array<string> */
    protected const array SKIPPED_ATTRIBUTE_PREFIX = [
        '__',
        '_',
        'lazyObject',
        'lazyProperties',
    ];

    /**
     * this cache is useless in production (PropertyInfoCacheExtractor), but so helpful in dev.
     *
     * @var array<string,Type|null>
     */
    private array $typeCache;

    public function __construct(
        protected ClassMetadataFactoryInterface $classMetadataFactory,
        protected PropertyTypeExtractorInterface $propertyTypeExtractor,
        protected PropertyAccessorInterface $propertyAccessor,
    ) {
        $this->typeCache = [];
    }

    public function setAutoDiscover(int $autoDiscover): void
    {
        $this->autoDiscover = $autoDiscover;
    }

    /**
     * @param SynergyEntityInterface $data
     * @param array<mixed>           $context
     *
     * @return array<mixed>
     */
    public function normalize($data, ?string $format = null, array $context = []): array
    {
        //        return (array)$data;

        $meta = $this->classMetadataFactory->getMetadataFor($data);
        $result = [];
        foreach ($meta->getAttributesMetadata() as $attributeMetadata) {
            //                echo("<br>attribute ".$attributeMetadata->getName().' : '.($attributeMetadata->isIgnored() ? 'ignored' : 'not ignored' ));
            if ($attributeMetadata->isIgnored()) {
                continue;
            }
            $attributeName = $attributeMetadata->getName();
            if ($this->skipAttribute($attributeName)) {
                continue;
            }

            try {
                $attributeValue = $this->propertyAccessor->getValue($data, $attributeName); // TODO : C'est ici des données sautent
            } catch (\Exception $e) {
                continue;
            }
            $key = $attributeName;
            $value = $attributeValue;
            $type = $this->getType($data::class, $key);
            if (null === $type) {
                continue;
            }

            // nullable types embeds real types...
            $type = TypeHelper::getInnerType($type);

            if ($type instanceof CollectionType) {
                //                if (self::isRelationCollection($type)) {
                // one to many
                if ($this->acceptDiscovery(self::DISCOVER_DESCENDING)) {
                    if (is_iterable($attributeValue)) {
                        $collection = [];
                        foreach ($attributeValue as $item) {
                            if ($item instanceof SynergyEntityInterface) {
                                $this->addDiscoveredEntity($item, self::DISCOVER_DESCENDING);
                                $collection[] = $item->getId(); // ok, does not fetch the entity lazy
                                //                            } else {
                                //                            $collection[] = $item; // ??
                                //                                throw new \InvalidArgumentException('SynergyEntityInterface expected. '.$item::class.' given');
                            }
                        }
                        $value = $collection;
                    } else {
                        throw new \InvalidArgumentException('Collection expected');
                    }
                } elseif ($this->isOneToMany($value)) {
                    // OneToMany is rebuilt in Synergy front
                    continue;
                }
            } elseif ($attributeValue instanceof \DateTimeInterface) {
                $value = $attributeValue->format('Y-m-d H:i:s');
            } elseif ($type instanceof ObjectType && $type->getClassName()) {
                if (is_a($type->getClassName(), SynergyEntityInterface::class, true)) {
                    // many to one
                    if ($attributeValue instanceof SynergyEntityInterface) {
                        $this->addDiscoveredEntity($attributeValue, self::DISCOVER_ASCENDING);
                    }
                    $key = $attributeName.'Id';
                    $value = $attributeValue?->getId(); // ok, does not fetch the entity lazy
                } else {
                    // object non Synergy => non sérialisé
                    //                        echo "<br>skip ! SynergyEntityInterface expected. ".$type->getClassName()." given";
                    continue;
                }
            }
            $result[$key] = $value;
        }

        return $result;
    }

    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof SynergyEntityInterface;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            SynergyEntityInterface::class => true,
        ];
    }

    private function addDiscoveredEntity(SynergyEntityInterface $entity, int $discoveryMode): void
    {
        if (!$this->acceptDiscovery($discoveryMode)) {
            return;
        }
        $entityId = $entity->getId();
        $uniqueKey = $entity::getEntityName().'-'.$entityId;
        if (null !== $entityId && !isset($this->discoveredEntities[$uniqueKey])) {
            $this->discoveredEntities[$uniqueKey] = $entity;
        }
    }

    /**
     * @return array<string,SynergyEntityInterface>
     */
    public function getDiscoveredEntities(): array
    {
        return $this->discoveredEntities;
    }

    public function clearDiscoveredEntities(): void
    {
        $this->discoveredEntities = [];
    }

    public function hasDiscoveredEntities(): bool
    {
        return (bool) count($this->discoveredEntities);
    }

    private function acceptDiscovery(int $discoveryMode): bool
    {
        return ($this->autoDiscover & $discoveryMode) === $discoveryMode;
    }

    private function skipAttribute(string $attributeName): bool
    {
        if (in_array($attributeName, self::SKIPPED_ATTRIBUTE, true)) {
            return true;
        }

        return array_any(self::SKIPPED_ATTRIBUTE_PREFIX, static fn ($prefix) => str_starts_with($attributeName, $prefix));
    }

    //    public static function isRelationCollection(CollectionType $type): bool
    //    {
    //        dd($type->getCollectionValueType());
    //        return
    //            $type->isCollection()
    //            && count($type->getCollectionValueTypes()) > 0
    //            && is_a($type->getClassName(), Collection::class, true);
    //
    //        // et si ça suffit pas, on peut piocher dans getCollectionValueTypes
    //        // et vérifier si on a une relation ?
    //        //        $collectionType = $type->getCollectionValueTypes();
    //    }/**
    protected function getType(string $objectClass, string $key): ?Type
    {
        $cacheKey = $objectClass.'::'.$key;

        return $this->typeCache[$cacheKey] ??= $this->propertyTypeExtractor->getType($objectClass, $key);
    }

    private function isOneToMany(mixed $value): bool
    {
        return $value instanceof Collection;
    }
}
