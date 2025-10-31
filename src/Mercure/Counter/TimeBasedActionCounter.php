<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Mercure\Counter;

use Efrogg\Synergy\Mercure\EntityAction;

class TimeBasedActionCounter implements ActionCounterInterface
{
    // time added at every entity to introduce hacked delay (in ms)
    protected int $hackWaitTime = 0;

    /**
     * @var array<string, int>
     */
    private array $firstAddTimes = [];
    /**
     * @var array<string, bool>
     */
    private array $isfirstAdd = [];

    /**
     * @param int $flushInterval time between 2 flushes (in ms)
     */
    public function __construct(
        private int $flushInterval = 1000,
        private bool $flushAtFirstIncrement = true,
    ) {
    }

    public function setFlushInterval(int $flushInterval): void
    {
        $this->flushInterval = $flushInterval;
    }

    public function increment(string $topicName, EntityAction $entityAction): void
    {
        usleep($this->hackWaitTime * 1000);
        if (!isset($this->firstAddTimes[$topicName])) {
            $this->firstAddTimes[$topicName] = $this->nowInMS();
            $this->isfirstAdd[$topicName] = true;
        }
    }

    public function getTopicToFlush(): array
    {
        $toFlush = [];
        foreach ($this->firstAddTimes as $topicName => $firstAddTime) {
            if ($this->flushAtFirstIncrement && ($this->isfirstAdd[$topicName] ?? false)) {
                $toFlush[] = $topicName;
            } elseif ($this->isExpired($firstAddTime)) {
                $toFlush[] = $topicName;
            }
        }

        return $toFlush;
    }

    public function clear(string $topicName): void
    {
        // clear isfirstAdd every time, so newf "needFlush" won't return true (or if is expired)
        unset($this->isfirstAdd[$topicName]);

        // flush firstAddTimes only if it is expired
        // here flush can be called for a isfirstAdd reason
        $firstAddTime = $this->firstAddTimes[$topicName] ?? null;
        if (null !== $firstAddTime && $this->isExpired($firstAddTime)) {
            $this->firstAddTimes[$topicName] = $this->nowInMS();
        }
    }

    private function nowInMS(): int
    {
        return (int) (microtime(true) * 1000);
    }

    private function isExpired(int $firstAddTime): bool
    {
        return $this->nowInMS() - $firstAddTime > $this->flushInterval;
    }
}
