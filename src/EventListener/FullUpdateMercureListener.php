<?php

declare(strict_types=1);

namespace Efrogg\Synergy\EventListener;

use Efrogg\Synergy\Context;
use Efrogg\Synergy\Event\MercureEntityActionEvent;
use Efrogg\Synergy\Mercure\EntityUpdater;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FullUpdateMercureListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            MercureEntityActionEvent::class => 'onMercureAction',
        ];
    }

    public function onMercureAction(MercureEntityActionEvent $event): void
    {
        // full update : forward all entities to the full update topic
        $event->addTopicAction(EntityUpdater::getFullUpdateTopic(Context::createDefaultContext()), $event->getEntityAction());
    }
}
