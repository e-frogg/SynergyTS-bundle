<?php

namespace Efrogg\Synergy\Mercure\Collector;

use Efrogg\Synergy\Mercure\Counter\ActionCounterInterface;
use Efrogg\Synergy\Mercure\EntityAction;
use Efrogg\Synergy\Mercure\MercureActionCollection;

class BufferedActionCollector implements ActionCollectorInterface
{
    public function __construct(
        private readonly ActionCollectorInterface $decorated,
        private readonly ActionCounterInterface $actionCounter,
    ) {
    }

    public function addTopicAction(string $topicName, EntityAction $entityAction): void
    {
        $this->decorated->addTopicAction($topicName, $entityAction);
        $this->actionCounter->increment($topicName, $entityAction);

        foreach ($this->actionCounter->getTopicToFlush() as $topicToFlush) {
            $this->flush($topicToFlush);
            $this->actionCounter->clear($topicToFlush);
        }
    }

    public function getAllTopicActions(): array
    {
        return $this->decorated->getAllTopicActions();
    }

    public function getTopicActions(string $topicName): MercureActionCollection
    {
        return $this->decorated->getTopicActions($topicName);
    }

    public function flush(?string $topicName = null): void
    {
        $this->decorated->flush($topicName);
    }

    public function clear(?string $topicName): void
    {
        $this->decorated->clear($topicName);
    }

    public function __destruct()
    {
        $this->flush();
    }
}
