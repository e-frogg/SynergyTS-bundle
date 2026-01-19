<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Event;

use Efrogg\Synergy\Entity\SynergyEntityInterface;

class TopicEntityDispatchEvent
{
    /** @var array<string> */
    private array $topics = [];

    public function __construct(
        public readonly SynergyEntityInterface $entity,
    ) {
    }

    public function addTopic(string $topic): void
    {
        if (!in_array($topic, $this->topics, true)) {
            $this->topics[] = $topic;
        }
    }

    /**
     * @return array<string>
     */
    public function getTopics(): array
    {
        return $this->topics;
    }
}
