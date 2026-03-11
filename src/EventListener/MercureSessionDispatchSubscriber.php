<?php

declare(strict_types=1);

namespace Efrogg\Synergy\EventListener;

use Efrogg\Synergy\Event\TopicEntityDispatchEvent;
use Efrogg\Synergy\Mercure\Session\MercureSessionTopicResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class MercureSessionDispatchSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private MercureSessionTopicResolverInterface $sessionTopicResolver,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TopicEntityDispatchEvent::class => 'dispatchSessionTopics',
        ];
    }

    public function dispatchSessionTopics(TopicEntityDispatchEvent $event): void
    {
        foreach ($this->sessionTopicResolver->findTopicsForEntity($event->entity) as $topic) {
            $event->addTopic($topic);
        }
    }
}
