<?php

declare(strict_types=1);

namespace Efrogg\Synergy\EventListener;

use Efrogg\Synergy\Mercure\Collector\ActionCollectorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;

readonly class WorkerSubscriber implements EventSubscriberInterface
{


    public function __construct(
        private ActionCollectorInterface $actionCollector,
    ) {
    }


    public static function getSubscribedEvents(): array
    {
        return [
            WorkerStoppedEvent::class => 'flushCollector',
            WorkerMessageHandledEvent::class => 'flushCollector'
        ];
    }

    public function flushCollector(): void
    {
        $this->actionCollector->flush();
    }
}
