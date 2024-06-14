<?php

declare(strict_types=1);

namespace Efrogg\Synergy\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Efrogg\Synergy\Acl\AclManager;
use Efrogg\Synergy\Entity\SynergyEntityInterface;
use Efrogg\Synergy\Exception\GrantException;

#[AsDoctrineListener(event: Events::preUpdate)]
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preRemove)]
readonly class EntityAclListener
{


    public function __construct(
        private AclManager $aclManager
    ) {
    }

    /**
     * @throws GrantException
     */
    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $entity = $event->getObject();
        if ($entity instanceof SynergyEntityInterface) {
            $this->aclManager->checkEntityIsGranted($entity, AclManager::UPDATE);
        }
    }

    /**
     * @throws GrantException
     */
    public function prePersist(PrePersistEventArgs $event): void
    {
        $entity = $event->getObject();
        if ($entity instanceof SynergyEntityInterface) {
            $this->aclManager->checkEntityIsGranted($entity, AclManager::CREATE);
        }
    }

    /**
     * @throws GrantException
     */
    public function preRemove(PreRemoveEventArgs $event): void
    {
        $entity = $event->getObject();
        if ($entity instanceof SynergyEntityInterface) {
            $this->aclManager->checkEntityIsGranted($entity, AclManager::DELETE);
        }
    }
}
