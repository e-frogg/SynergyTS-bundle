<?php

declare(strict_types=1);

namespace Efrogg\Synergy\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

readonly class KernelListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // if user agent is 'bruno-runtime*'
        // json encode flags
        $request = $event->getRequest();
        $userAgent = $request->headers->get('User-Agent', '');
        if (str_starts_with($userAgent, 'bruno-runtime')) {
            $response = $event->getResponse();
            if ($response instanceof JsonResponse) {
                $response->setEncodingOptions(JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG);
            }
        }
    }
}
