<?php

namespace Efrogg\Synergy\Mercure\Collector;

use Efrogg\Synergy\Mercure\ActionNormalizer;
use Efrogg\Synergy\Mercure\EntityAction;
use Efrogg\Synergy\Mercure\MercureActionCollection;
use JsonException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class ActionCollector implements ActionCollectorInterface
{


    public function __construct(
        private readonly ActionNormalizer $actionNormalizer,
        private readonly HubInterface $hub
    ) {
    }

    /**
     * @var array<string, MercureActionCollection>
     */
    protected array $actionsByTopic = [];

    public function addTopicAction(string $topicName, EntityAction $entityAction): void
    {
        if (!isset($this->actionsByTopic[$topicName])) {
            $this->actionsByTopic[$topicName] = new MercureActionCollection();
        }
        $this->actionsByTopic[$topicName]->addAction($entityAction);
    }

    /**
     * @return array<string, MercureActionCollection>
     */
    public function getAllTopicActions(): array
    {
        return $this->actionsByTopic;
    }

    public function getTopicActions(string $topicName): MercureActionCollection
    {
        return $this->actionsByTopic[$topicName] ?? new MercureActionCollection();
    }

    /**
     * @throws JsonException
     */
    public function flush(?string $topicName = null): void
    {
        if (null === $topicName) {
            array_map(
                $this->flush(...),
                array_keys($this->actionsByTopic)
            );
            return;
        }

        $actionCollection = $this->actionsByTopic[$topicName] ?? new MercureActionCollection();
//        dump(sprintf('flushing %s (%d actions)', $topicName, $actionCollection->count()));
//        foreach ($actionCollection->getActions() as $action) {
//            dump(sprintf('action %s : %d entities', $action::getAction(), $action->count()));
//        }
        $actionsJson = json_encode(
            array_map(
                $this->actionNormalizer->normalize(...),
                $actionCollection->getActions(),
            ),
            JSON_THROW_ON_ERROR
        );

        $update = new Update(
            $topicName,
            $actionsJson,
            true // private = auth jwt
        );

        // Publisher's JWT must contain this topic, a URI template it matches or * in mercure.publish or you'll get a 401
        // Subscriber's JWT must contain this topic, a URI template it matches or * in mercure.subscribe to receive the update
        $this->hub->publish($update);
        $this->clear($topicName);
    }

    public function clear(?string $topicName): void
    {
        if (null === $topicName) {
            // clear all
            $this->actionsByTopic = [];
            return;
        }

        // clear one topic only
        unset($this->actionsByTopic[$topicName]);
    }

    public function __destruct()
    {
        $this->flush();
    }
}
