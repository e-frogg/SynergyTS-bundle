<?php

namespace Efrogg\Synergy\Serializer\Normalizer;

use DateTimeInterface;
use Doctrine\Common\Collections\Collection;
use Efrogg\Synergy\Entity\SynergyEntityInterface;
use Exception;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class EntityNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    public const int DISCOVER_NONE = 0;
    /**
     * discover oneToMany (ex : vehicle -> vehicleModel)
     */
    public const int DISCOVER_ASCENDING = 1;

    /**
     * discover manyToOne (ex : vehicleModel -> vehicles)
     */
    public const int DISCOVER_DESCENDING = 2;

    public const int DISCOVER_ALL = self::DISCOVER_ASCENDING | self::DISCOVER_DESCENDING;

    /**
     * @var array<string,SynergyEntityInterface>
     */
    private array $discoveredEntities = [];

    /**
     * activate entity discovery for relations (oneToMany, manyToOne)
     *
     * @var int
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

    public function __construct(
        protected ClassMetadataFactoryInterface $classMetadataFactory,
        protected PropertyTypeExtractorInterface $propertyTypeExtractor,
        protected PropertyAccessorInterface $propertyAccessor
    ) {
//        $this->propertyAccessor ??= PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param int $autoDiscover
     */
    public function setAutoDiscover(int $autoDiscover): void
    {
        $this->autoDiscover = $autoDiscover;
    }

    /**
     * @param SynergyEntityInterface $object
     * @param array<mixed>         $context
     *
     * @return array<mixed>
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
//        echo("<br><br>class ".$object::class);
        $meta = $this->classMetadataFactory->getMetadataFor($object);
//        echo(" -> ".$meta->getName());
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

            $types = $this->propertyTypeExtractor->getTypes($object::class, $attributeName);
            if (null === $types) {
                continue;
            }
            try {
                $attributeValue = $this->propertyAccessor->getValue($object, $attributeName);
            } catch (Exception $e) {
                continue;
            }
            $key = $attributeName;
            $value = $attributeValue;
            foreach ($types as $type) {
                if ($type->isCollection()) {
                    if (self::isRelationCollection($type)) {
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
                        }
                    }
                } elseif ($attributeValue instanceof DateTimeInterface) {
                    $value = $attributeValue->format('Y-m-d H:i:s');
                } elseif ($type->getClassName()) {
                    if (is_a($type->getClassName(), SynergyEntityInterface::class, true)) {
                        // many to one
                        if ($attributeValue instanceof SynergyEntityInterface) {
                            $this->addDiscoveredEntity($attributeValue, self::DISCOVER_ASCENDING);
                        }
                        $key = $attributeName . 'Id';
                        $value = $attributeValue?->getId(); // ok, does not fetch the entity lazy
                    } else {
                        // object non Synergy => non sérialisé
//                        echo "<br>skip ! SynergyEntityInterface expected. ".$type->getClassName()." given";
                        continue(2);
                    }
                }
            }
            $result[$key] = $value;
        }

        return $result;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
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
        $uniqueKey = $entity::getEntityName() . '-' . $entityId;
        if ($entityId !== null && !isset($this->discoveredEntities[$uniqueKey])) {
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
        return (bool)count($this->discoveredEntities);
    }

    /**
     * @param int $discoveryMode
     *
     * @return bool
     */
    private function acceptDiscovery(int $discoveryMode): bool
    {
        return ($this->autoDiscover & $discoveryMode) === $discoveryMode;
    }

    private function skipAttribute(string $attributeName): bool
    {
        if (in_array($attributeName, self::SKIPPED_ATTRIBUTE, true)) {
            return true;
        }

        foreach (self::SKIPPED_ATTRIBUTE_PREFIX as $prefix) {
            if (str_starts_with($attributeName, $prefix)) {
                return true;
            }
        }
        return false;
    }

    public static function isRelationCollection(Type $type): bool
    {
        return
            $type->isCollection()
            && count($type->getCollectionValueTypes()) > 0
            && is_a($type->getClassName(), Collection::class, true);

        // et si ça suffit pas, on peut piocher dans getCollectionValueTypes
        // et vérifier si on a une relation ?
//        $collectionType = $type->getCollectionValueTypes();
    }
}
