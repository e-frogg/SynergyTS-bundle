<?php

namespace Efrogg\Synergy\Mercure\Counter;

use Efrogg\Synergy\Mercure\EntityAction;

interface ActionCounterInterface
{
    public function increment(string $topicName, EntityAction $entityAction): void;

    /**
     * @return array<string>
     */
    public function getTopicToFlush(): array;

    public function clear(string $topicName): void;
}
