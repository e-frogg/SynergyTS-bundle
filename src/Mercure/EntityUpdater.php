<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Mercure;

use Efrogg\Synergy\Context;
use Efrogg\Synergy\Entity\SynergyEntityInterface;
use Efrogg\Synergy\Event\MercureEntityActionEvent;
use Efrogg\Synergy\Mercure\Collector\ActionCollectorInterface;
use JsonException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class EntityUpdater
{

    private bool $enabled = true;

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ActionCollectorInterface $actionCollector
    ) {
    }

    /**
     * @param Context $context
     *
     * @return string
     * @deprecated, a supprimer dès que possible
     *  pour mettre dans un listener
     */
    public static function getFullUpdateTopic(Context $context): string
    {
        return '/entityUpdates/full';
    }

    public function dispatchRemove(SynergyEntityInterface $entity, string|int $entityId): void
    {
        $actions = new MercureActionCollection([
            new EntityRemoveAction([$entity], [$entityId])
        ]);
        $this->dispatchActions($actions);
    }

    public function dispatchNew(SynergyEntityInterface $entity): void
    {
        $actions = new MercureActionCollection([
            new EntityAddAction([$entity])
        ]);
        $this->dispatchActions($actions);
    }

    public function dispatchUpdate(SynergyEntityInterface $entity): void
    {
        $actions = new MercureActionCollection([
            new EntityUpdateAction([$entity])
        ]);
        $this->dispatchActions($actions);
    }


    /**
     * @param array<SynergyEntityInterface> $entities
     *
     * @return void
     */
    public function dispatchFullUpdate(array $entities): void
    {
        $actions = new MercureActionCollection();
        $actions->addAction(new EntityFullUpdateAction($entities));
        $this->dispatchActions($actions);
    }

    /**
     * @throws ExceptionInterface
     * @throws JsonException
     */
    private function dispatchActions(MercureActionCollection $actions): void
    {
        if (!$this->enabled) {
            return;
        }

        // split actions by topic
        foreach ($actions->getActions() as $action) {
            $topicEvent = new MercureEntityActionEvent($action);
            $this->eventDispatcher->dispatch($topicEvent);

            foreach ($topicEvent->getTopicActions() as $topic => $entityAction) {
                $this->actionCollector->addTopicAction($topic, $action);
            }
        }
//        $this->actionCollector->flush();

    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }
}
