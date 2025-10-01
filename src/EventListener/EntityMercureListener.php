<?php

declare(strict_types=1);

namespace Efrogg\Synergy\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Efrogg\Synergy\Entity\SynergyEntityInterface;
use Efrogg\Synergy\Mercure\EntityUpdater;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::preRemove)]
#[AsDoctrineListener(event: Events::postRemove)]
class EntityMercureListener
{
    /**
     * @var array<string,int|string>
     */
    private array $removedObjects;

    public function __construct(
        private readonly EntityUpdater $entityUpdater,
        private readonly EntityChangesetHolder $entityChangesetHolder,
    ) {
        $this->removedObjects = [];
    }

    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $entity = $event->getObject();
        if ($entity instanceof SynergyEntityInterface) {
            $this->entityChangesetHolder->setChangeset($entity, $event->getEntityChangeSet());
        }
    }

    public function postUpdate(PostUpdateEventArgs $event): void
    {
        $entity = $event->getObject();
        if ($entity instanceof SynergyEntityInterface) {
            $this->entityUpdater->dispatchUpdate($entity);
        }
    }

    public function postPersist(PostPersistEventArgs $event): void
    {
        $entity = $event->getObject();
        if ($entity instanceof SynergyEntityInterface) {
            $this->entityUpdater->dispatchNew($entity);
        }
    }

    public function preRemove(PreRemoveEventArgs $event): void
    {
        $entity = $event->getObject();
        if ($entity instanceof SynergyEntityInterface) {
            $id = $entity->getId();
            if (null !== $id) {
                $hash = spl_object_hash($entity);
                $this->removedObjects[$hash] = $id;
            }
        }
    }

    public function postRemove(PostRemoveEventArgs $event): void
    {
        $entity = $event->getObject();
        if ($entity instanceof SynergyEntityInterface) {
            $hash = spl_object_hash($entity);
            if (isset($this->removedObjects[$hash])) {
                $entityId = $this->removedObjects[$hash];
                $entity->setId($entityId);          // reinject

                unset($this->removedObjects[$hash]);
                $this->entityUpdater->dispatchRemove($entity, $entityId);
                //                throw new \Exception('postRemove reimplement setId');
            }
        }
    }
}
