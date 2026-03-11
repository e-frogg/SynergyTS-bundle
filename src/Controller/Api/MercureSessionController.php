<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Controller\Api;

use Efrogg\Synergy\Acl\AclManager;
use Efrogg\Synergy\Controller\Trait\JsonRequestTrait;
use Efrogg\Synergy\Mercure\Session\MercureSessionManager;
use Efrogg\Synergy\Mercure\Session\MercureSessionOwnerResolver;
use Efrogg\Synergy\Mercure\Session\MercureSessionSelectorResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\Twig\MercureExtension;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/entity/mercure-session', name: 'Synergy_mercure_session_')]
class MercureSessionController extends AbstractController
{
    use JsonRequestTrait;

    public function __construct(
        private readonly MercureSessionManager $sessionManager,
        private readonly MercureSessionOwnerResolver $ownerResolver,
        private readonly MercureSessionSelectorResolver $selectorResolver,
        private readonly MercureExtension $mercureExtension,
        private readonly AclManager $aclManager,
    ) {
    }

    /**
     * @throws \JsonException
     */
    #[Route('', name: 'create', methods: ['POST'], priority: 20)]
    public function create(Request $request): JsonResponse
    {
        $this->aclManager->setEnabled(true);

        $payload = $this->extractJson($request);
        $ownerId = $this->ownerResolver->resolveOwnerId();
        $entityKeys = $this->selectorResolver->resolveEntityKeys($payload->get('selectors', []));
        $session = $this->sessionManager->createSession($ownerId, $entityKeys, $this->resolveTtlSeconds($payload));

        return $this->buildSessionResponse($session);
    }

    /**
     * @throws \JsonException
     */
    #[Route('/{sessionId}', name: 'replace', methods: ['PUT'], priority: 20)]
    public function replace(string $sessionId, Request $request): JsonResponse
    {
        $this->aclManager->setEnabled(true);

        $payload = $this->extractJson($request);
        $ownerId = $this->ownerResolver->resolveOwnerId();
        $entityKeys = $this->selectorResolver->resolveEntityKeys($payload->get('selectors', []));

        try {
            $session = $this->sessionManager->replaceSession($sessionId, $ownerId, $entityKeys, $this->resolveTtlSeconds($payload));
        } catch (\OutOfBoundsException) {
            throw $this->createNotFoundException('Mercure session not found.');
        } catch (\RuntimeException) {
            throw $this->createAccessDeniedException('Mercure session is not owned by current user.');
        }

        return $this->buildSessionResponse($session);
    }

    #[Route('/{sessionId}', name: 'delete', methods: ['DELETE'], priority: 20)]
    public function delete(string $sessionId): Response
    {
        $ownerId = $this->ownerResolver->resolveOwnerId();

        try {
            $this->sessionManager->deleteSession($sessionId, $ownerId);
        } catch (\OutOfBoundsException) {
            throw $this->createNotFoundException('Mercure session not found.');
        } catch (\RuntimeException) {
            throw $this->createAccessDeniedException('Mercure session is not owned by current user.');
        }

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * @param array{id:string,topic:string,expiresAt:int} $session
     */
    private function buildSessionResponse(array $session): JsonResponse
    {
        $topic = $session['topic'];

        return new JsonResponse([
            'sessionId' => $session['id'],
            'topic' => $topic,
            'mercureUrl' => $this->mercureExtension->mercure($topic, ['subscribe' => $topic]),
            'expiresAt' => $session['expiresAt'],
        ]);
    }

    private function resolveTtlSeconds(\Symfony\Component\HttpFoundation\ParameterBag $payload): ?int
    {
        $ttlSeconds = $payload->all()['ttlSeconds'] ?? null;
        if (null === $ttlSeconds || !is_numeric($ttlSeconds)) {
            return null;
        }

        return (int) $ttlSeconds;
    }
}
