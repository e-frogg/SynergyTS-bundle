<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Serializer\Normalizer;

use Efrogg\Synergy\Entity\SynergyEntityInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class EntityCollectionNormalizer
{
    protected static int $defaultAutoDiscoverLevels = 2;

    /**
     * for avoiding circular references and multiple serialization of the same entity.
     *
     * @var array<int,bool>
     */
    private array $normalizedIndex = [];

    public function __construct(
        private readonly NormalizerInterface $normalizer,
        private readonly EntityNormalizer $genericEntityNormalizer,
    ) {
    }

    /**
     * @param iterable<SynergyEntityInterface> $entities
     *
     * @return array<string, array<string,array<array<string,mixed>>>>
     *
     * @throws ExceptionInterface
     */
    public function normalize(iterable $entities, ?int $autoDiscoverLevels = null): array
    {
        $autoDiscoverLevels ??= self::$defaultAutoDiscoverLevels;
        $this->resetNormalizedIndex();
        /** @var array<string, array<string,array<array<string,mixed>>>> $data */
        $data = [];
        $this->_normalizeLevel($entities, $data, $autoDiscoverLevels);

        return array_values($data); // @phpstan-ignore-line
    }

    /**
     * @param iterable<SynergyEntityInterface>                        $entities
     * @param array<string, array<string,array<array<string,mixed>>>> $data
     *
     * @throws ExceptionInterface
     */
    private function _normalizeLevel(iterable $entities, array &$data, int $autoDiscoverLevels): void
    {
        $this->genericEntityNormalizer->clearDiscoveredEntities();
        foreach ($entities as $entity) {
            if (!$entity instanceof SynergyEntityInterface) {
                throw new \InvalidArgumentException('Normalizer error. Only normalize array ot SynergyEntity');
            }
            $entityName = $entity::getEntityName();

            // évite les doublons
            //            $indexKey = $entityName . '-' . $entity->getId();
            $indexKey = spl_object_id($entity);
            if (isset($this->normalizedIndex[$indexKey])) {
                // double vérif, mais n'arrive pas normalement
                continue;
            }

            $data[$entityName] ??= [
                'entityName' => $entityName,
                'entities' => [],
            ];
            $this->normalizedIndex[$indexKey] = true;
            /** @var array<string,mixed> $normalized */
            $normalized = $this->normalizer->normalize($entity, 'json', ['groups' => ['Default']]);
            $data[$entityName]['entities'][] = $normalized;
        }
        if ($autoDiscoverLevels > 0 && $this->genericEntityNormalizer->hasDiscoveredEntities()) {
            // filter out already serialized entities
            $discovers = array_filter($this->genericEntityNormalizer->getDiscoveredEntities(), function ($discoveredEntity) {
                return !isset($this->normalizedIndex[$discoveredEntity::getEntityName().'-'.$discoveredEntity->getId()]);
            });

            if (count($discovers) > 0) {
                $this->_normalizeLevel($discovers, $data, $autoDiscoverLevels - 1);
            }
        }
    }

    private function resetNormalizedIndex(): void
    {
        $this->normalizedIndex = [];
    }
}
