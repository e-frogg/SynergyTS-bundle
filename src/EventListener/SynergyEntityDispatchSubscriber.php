<?php

declare(strict_types=1);

namespace Efrogg\Synergy\EventListener;

use Efrogg\Synergy\Entity\SynergyEntityInterface;
use Efrogg\Synergy\Event\MercureEntityActionEvent;
use Efrogg\Synergy\Event\TopicEntityDispatchEvent;
use Efrogg\Synergy\Mercure\EntityAction;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SynergyEntityDispatchSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MercureEntityActionEvent::class => 'onEntityAction',
        ];
    }

    public function onEntityAction(MercureEntityActionEvent $event): void
    {
        // regroupe les entités par topic, si multiples entités
        /** @var array<string, EntityAction> $topicActions */
        $topicActions = [];
        foreach ($event->getEntities() as $entity) {
            $topicDispatchEvent = new TopicEntityDispatchEvent($entity);
            $this->eventDispatcher->dispatch($topicDispatchEvent);

            foreach ($topicDispatchEvent->getTopics() as $topic) {
                if (isset($topicActions[$topic])) {
                    // on ajoute l'entité courante à l'action existante
                    $topicActions[$topic]->addEntity($entity);
                } else {
                    // on crée une nouvelle action, avec l'entité courante
                    $topicActions[$topic] = $this->cloneAction($event->getEntityAction(), [$entity]);
                }
            }
        }

        foreach ($topicActions as $topic => $action) {
            $event->addTopicAction($topic, $action);
        }
    }

    /**
     * @param array<SynergyEntityInterface> $entities
     */
    private function cloneAction(EntityAction $entityAction, array $entities): EntityAction
    {
        return (clone $entityAction)->setEntities($entities);
    }
}
