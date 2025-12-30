<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Data;

use Efrogg\Synergy\Entity\SynergyEntityInterface;
use Efrogg\Synergy\Serializer\Normalizer\EntityCollectionNormalizer;
use Efrogg\Synergy\Serializer\Normalizer\EntityNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mercure\Twig\MercureExtension;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class EntityResponseBuilder
{
    private int $discoverLevel = 0;

    public function __construct(
        private readonly EntityCollectionNormalizer $entityCollectionNormalizer,
        private readonly MercureExtension $mercureExtension,
        private readonly EntityNormalizer $genericEntityNormalizer,
    ) {
    }

    public function setDiscoverLevel(int $discoverLevel, int $autoDiscoverMode = EntityNormalizer::DISCOVER_ALL): void
    {
        $this->discoverLevel = $discoverLevel;
        $this->genericEntityNormalizer->setAutoDiscover($discoverLevel ? $autoDiscoverMode : EntityNormalizer::DISCOVER_NONE);
    }

    /**
     * @param array<SynergyEntityInterface>    $entities
     * @param ?array<string,array<int|string>> $mainIds
     * @param string|array<string>|null        $mercureTopics
     *
     * @throws ExceptionInterface
     */
    public function buildResponse(array $entities, ?array $mainIds = null, string|array|null $mercureTopics = null, ?int $totalCount = null): JsonResponse
    {
        $data = [
            'data' => $this->entityCollectionNormalizer->normalize($entities, $this->discoverLevel),
        ];
        if (null !== $mercureTopics) {
            $data['mercureUrl'] = $this->mercureExtension->mercure($mercureTopics, ['subscribe' => $mercureTopics]);
        }
        if (null !== $mainIds) {
            $data['mainIds'] = $mainIds;
        }
        if (null !== $totalCount) {
            $data['totalCount'] = $totalCount;
        }

        return new JsonResponse($data);
    }

    /**
     * @param array<SynergyEntityInterface> $entities
     * @param string|array<string>|null     $mercureTopics
     *
     * @throws ExceptionInterface
     */
    public function buildResponseFromCollection(array $entities, string|array|null $mercureTopics = null, ?int $totalCount = null): JsonResponse
    {
        return $this->buildResponse(
            $entities,
            $this->computeMainIds($entities),
            $mercureTopics,
            $totalCount
        );
    }

    /**
     * @param array<SynergyEntityInterface> $entities
     *
     * @return array<string,array<int|string>>
     */
    private function computeMainIds(array $entities): array
    {
        $mainIds = [];
        foreach ($entities as $entity) {
            $mainIds[$entity::getEntityName()][] = $entity->getId();
        }

        return array_map(array_filter(...), $mainIds);
    }

    /**
     * @param string|array<string>|null $mercureTopics
     *
     * @throws ExceptionInterface
     */
    public function buildResponseFromSearchResult(SearchResult $result, string|array|null $mercureTopics = null): JsonResponse
    {
        return $this->buildResponse(
            $result->getEntities(),
            $result->getMainIds(),
            $mercureTopics,
            $result->getTotalCount()
        );
    }
}
