<?php

namespace Efrogg\Synergy\Mercure\Collector;

use Efrogg\Synergy\Mercure\EntityAction;
use Efrogg\Synergy\Mercure\MercureActionCollection;

interface ActionCollectorInterface
{
    public function addTopicAction(string $topicName, EntityAction $entityAction): void;

    /**
     * @return array<string, MercureActionCollection>
     */
    public function getAllTopicActions(): array;

    public function getTopicActions(string $topicName): MercureActionCollection;

    public function flush(?string $topicName = null): void;

    public function clear(?string $topicName): void;
}
