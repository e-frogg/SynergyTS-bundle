<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Mercure;

use Efrogg\Synergy\Context;
use Efrogg\Synergy\Entity\SynergyEntityInterface;
use Efrogg\Synergy\Event\MercureEntityActionEvent;
use Efrogg\Synergy\Serializer\Normalizer\EntityCollectionNormalizer;
use JsonException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class EntityUpdater
{

    private bool $enabled = true;

    public function __construct(
        private readonly HubInterface $hub,
        private readonly EntityCollectionNormalizer $entityCollectionNormalizer,
        private readonly EventDispatcherInterface $eventDispatcher,
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
        /** @var array<string,MercureActionCollection> $actionsByTopic */
        $actionsByTopic = [];
        foreach ($actions->getActions() as $action) {
            $topicEvent = new MercureEntityActionEvent($action);
            $this->eventDispatcher->dispatch($topicEvent);

            foreach ($topicEvent->getTopicActions() as $topic => $entityAction) {
                if (!isset($actionsByTopic[$topic])) {
                    $actionsByTopic[$topic] = new MercureActionCollection();
                }
                $actionsByTopic[$topic]->addAction($entityAction);
            }
        }

        foreach ($actionsByTopic as $topic => $topicMercureActions) {
            $actionsJson = json_encode(
                array_map(
                    $this->normalizeAction(...),
                    $topicMercureActions->getActions(),
                ),
                JSON_THROW_ON_ERROR
            );

            $update = new Update(
                $topic,
                $actionsJson,
                true // private = auth jwt
            );

            // Publisher's JWT must contain this topic, a URI template it matches or * in mercure.publish or you'll get a 401
            // Subscriber's JWT must contain this topic, a URI template it matches or * in mercure.subscribe to receive the update
            $this->hub->publish($update);
        }
    }

    /**
     * @param EntityAction $action
     *
     * @return array<string,mixed>
     * @throws ExceptionInterface
     */
    public function normalizeAction(EntityAction $action): array
    {
        $data = $this->entityCollectionNormalizer->normalize($action->getEntities());
        return [
            'action' => $action::getAction(),
            'data'   => $data,
            ...$action::getAdditionalParameters()
        ];
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
