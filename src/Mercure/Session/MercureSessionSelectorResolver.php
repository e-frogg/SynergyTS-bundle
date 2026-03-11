<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Mercure\Session;

use Doctrine\ORM\EntityManagerInterface;
use Efrogg\Synergy\Acl\AclManager;
use Efrogg\Synergy\Entity\SynergyEntityInterface;
use Efrogg\Synergy\Helper\EntityHelper;

readonly class MercureSessionSelectorResolver
{
    public function __construct(
        private EntityHelper $entityHelper,
        private EntityManagerInterface $entityManager,
        private AclManager $aclManager,
    ) {
    }

    /**
     * @return array<string>
     */
    public function resolveEntityKeys(mixed $selectors): array
    {
        if (!is_array($selectors)) {
            return [];
        }

        $entityKeys = [];
        foreach ($selectors as $selector) {
            if (!is_array($selector)) {
                continue;
            }

            $entityName = $selector['entity'] ?? null;
            $ids = $selector['ids'] ?? null;
            if (!is_string($entityName) || !is_array($ids)) {
                continue;
            }

            $entityClass = $this->entityHelper->findEntityClass($entityName);
            if (null === $entityClass) {
                continue;
            }

            $repository = $this->entityManager->getRepository($entityClass);
            foreach ($ids as $id) {
                if (!is_string($id) && !is_int($id)) {
                    continue;
                }

                $entity = $repository->find($id);
                if (!$entity instanceof SynergyEntityInterface) {
                    continue;
                }

                if (!$this->aclManager->isEntityGranted($entity, AclManager::READ)) {
                    continue;
                }

                $entityId = $entity->getId();
                if (null === $entityId) {
                    continue;
                }

                $entityKeys[] = MercureSessionManager::buildEntityKey($entity::class, $entityId);
            }
        }

        return array_values(array_unique($entityKeys));
    }
}
