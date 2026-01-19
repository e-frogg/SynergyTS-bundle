<?php

namespace Efrogg\Synergy\Mercure;

use Efrogg\Synergy\Serializer\Normalizer\EntityCollectionNormalizer;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

readonly class ActionNormalizer
{
    public function __construct(
        private EntityCollectionNormalizer $entityCollectionNormalizer
    ) {
    }

    /**
     * @return array<string,mixed>
     *
     * @throws ExceptionInterface
     */
    public function normalize(EntityAction $entityAction): array
    {
        $data = $this->entityCollectionNormalizer->normalize($entityAction->getEntities());

        return [
            'action' => $entityAction::getAction(),
            'data' => $data,
            ...$entityAction::getAdditionalParameters(),
        ];
    }
}
