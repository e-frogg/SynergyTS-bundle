<?php

namespace Efrogg\Synergy\Event;

use Efrogg\Synergy\Entity\SynergyEntityInterface;
use Efrogg\Synergy\Mercure\EntityAction;

class MercureEntityActionEvent
{
    /** @var array<string,EntityAction> */
    private array $topicActions = [];

    public function __construct(
        private readonly EntityAction $entityAction,
    ) {
    }

    public function getEntityAction(): EntityAction
    {
        return $this->entityAction;
    }

    public function getActionName(): string
    {
        return $this->entityAction::getAction();
    }

    /**
     * @return array<SynergyEntityInterface>
     */
    public function getEntities(): array
    {
        return $this->entityAction->getEntities();
    }

    public function addTopicAction(string $topic, EntityAction $action): self
    {
        // TODO : merge si on en a plusieurs

        //        if(isset($this->topicActions[$topic])) {
        //            $existingAction = $this->topicActions[$topic];
        //            $mergedEntities = array_merge($existingAction->getEntities(), $action->getEntities());
        //            $existingAction->setEntities($mergedEntities);
        //            $this->topicActions[$topic] = $existingAction;
        //            return $this;
        //        }
        $this->topicActions[$topic] = $action;

        return $this;
    }

    /**
     * key : topic
     * value : EntityAction.
     *
     * @return array<string,EntityAction>
     */
    public function getTopicActions(): array
    {
        return $this->topicActions;
    }
}
