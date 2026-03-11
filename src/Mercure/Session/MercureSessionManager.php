<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Mercure\Session;

use Efrogg\Synergy\Entity\SynergyEntityInterface;
use Psr\Cache\CacheItemPoolInterface;

class MercureSessionManager implements MercureSessionTopicResolverInterface
{
    private const string SESSION_CACHE_PREFIX = 'synergy_mercure_session_';
    private const string ENTITY_INDEX_CACHE_PREFIX = 'synergy_mercure_session_idx_';

    public function __construct(
        private readonly CacheItemPoolInterface $cachePool,
        private readonly int $defaultTtlSeconds = 3600,
    ) {
    }

    /**
     * Example: `App\Entity\Content#42`.
     */
    public static function buildEntityKey(string $entityClass, string|int $entityId): string
    {
        return $entityClass.'#'.$entityId;
    }

    /**
     * @param array<string> $entityKeys
     *                                  Example input: `['App\Entity\Content#42', 'App\Entity\Content#84']`
     *
     * @return array{id:string,ownerId:string,topic:string,entityKeys:array<string>,ttlSeconds:int,expiresAt:int}
     *
     * Example return shape:
     * `[
     *   'id' => 'ab12...',
     *   'ownerId' => 'user-7',
     *   'topic' => 'session_ab12...',
     *   'entityKeys' => ['App\Entity\Content#42'],
     *   'ttlSeconds' => 3600,
     *   'expiresAt' => 1710003600,
     * ]`
     */
    public function createSession(string $ownerId, array $entityKeys, ?int $ttlSeconds = null): array
    {
        $ttlSeconds = $this->normalizeTtl($ttlSeconds);
        $sessionId = bin2hex(random_bytes(16));
        $session = [
            'id' => $sessionId,
            'ownerId' => $ownerId,
            'topic' => 'session_'.$sessionId,
            'entityKeys' => $this->normalizeEntityKeys($entityKeys),
            'ttlSeconds' => $ttlSeconds,
            'expiresAt' => time() + $ttlSeconds,
        ];

        $this->persistSession($session);
        $this->addSessionToIndexes($sessionId, $session['entityKeys']);

        return $session;
    }

    /**
     * @param array<string> $entityKeys
     *                                  Example input: `['App\Entity\Content#84']`
     *
     * @return array{id:string,ownerId:string,topic:string,entityKeys:array<string>,ttlSeconds:int,expiresAt:int}
     *                                                                                                            Example return shape: `['id' => 'ab12...', 'topic' => 'session_ab12...', 'entityKeys' => ['App\Entity\Content#84'], ...]`.
     */
    public function replaceSession(string $sessionId, string $ownerId, array $entityKeys, ?int $ttlSeconds = null): array
    {
        $session = $this->getOwnedSession($sessionId, $ownerId);
        $previousEntityKeys = $session['entityKeys'];
        $ttlSeconds = $this->normalizeTtl($ttlSeconds ?? $session['ttlSeconds']);

        $session['entityKeys'] = $this->normalizeEntityKeys($entityKeys);
        $session['ttlSeconds'] = $ttlSeconds;
        $session['expiresAt'] = time() + $ttlSeconds;

        $this->persistSession($session);
        $this->removeSessionFromIndexes($sessionId, $previousEntityKeys);
        $this->addSessionToIndexes($sessionId, $session['entityKeys']);

        return $session;
    }

    public function deleteSession(string $sessionId, string $ownerId): void
    {
        $session = $this->getOwnedSession($sessionId, $ownerId);
        $this->removeSessionFromIndexes($sessionId, $session['entityKeys']);
        $this->cachePool->deleteItem($this->getSessionCacheKey($sessionId));
    }

    /**
     * @return array{id:string,ownerId:string,topic:string,entityKeys:array<string>,ttlSeconds:int,expiresAt:int}|null
     *                                                                                                                 Example return shape: `['id' => 'ab12...', 'ownerId' => 'user-7', 'entityKeys' => ['App\Entity\Content#42'], ...]` or `null`.
     */
    public function getSession(string $sessionId): ?array
    {
        $item = $this->cachePool->getItem($this->getSessionCacheKey($sessionId));
        if (!$item->isHit()) {
            return null;
        }

        $session = $item->get();
        if (!is_array($session)) {
            $this->cachePool->deleteItem($this->getSessionCacheKey($sessionId));

            return null;
        }

        $ownerId = $session['ownerId'] ?? null;
        $topic = $session['topic'] ?? null;
        $ttlSeconds = $session['ttlSeconds'] ?? null;
        $expiresAt = $session['expiresAt'] ?? null;

        if (!is_string($ownerId) || !is_string($topic) || !is_int($ttlSeconds) || !is_int($expiresAt)) {
            $this->cachePool->deleteItem($this->getSessionCacheKey($sessionId));

            return null;
        }

        return [
            'id' => $sessionId,
            'ownerId' => $ownerId,
            'topic' => $topic,
            'entityKeys' => $this->normalizeEntityKeys($session['entityKeys'] ?? []),
            'ttlSeconds' => $ttlSeconds,
            'expiresAt' => $expiresAt,
        ];
    }

    /**
     * @return array<string>
     *                       Example return: `['session_ab12...']`.
     */
    public function findTopicsForEntity(SynergyEntityInterface $entity): array
    {
        $entityId = $entity->getId();
        if (null === $entityId) {
            return [];
        }

        return $this->findTopicsForEntityKey(
            self::buildEntityKey($entity::class, $entityId)
        );
    }

    /**
     * @return array<string>
     *                       Example return: `['session_ab12...', 'session_cd34...']`.
     */
    public function findTopicsForEntityKey(string $entityKey): array
    {
        $indexCacheKey = $this->getEntityIndexCacheKey($entityKey);
        $indexItem = $this->cachePool->getItem($indexCacheKey);
        if (!$indexItem->isHit()) {
            return [];
        }

        $sessionIds = $this->normalizeSessionIdList($indexItem->get());
        if ([] === $sessionIds) {
            return [];
        }

        $validSessionIds = [];
        $topics = [];

        foreach ($sessionIds as $sessionId) {
            // The reverse index is intentionally best-effort: stale entries are repaired lazily here instead of scanning all sessions on each entity update.
            $session = $this->getSession($sessionId);
            if (null === $session) {
                continue;
            }

            if (!in_array($entityKey, $session['entityKeys'], true)) {
                continue;
            }

            $validSessionIds[] = $sessionId;
            $topics[] = $session['topic'];
        }

        $validSessionIds = array_values(array_unique($validSessionIds));
        if ($sessionIds !== $validSessionIds) {
            if ([] === $validSessionIds) {
                $this->cachePool->deleteItem($indexCacheKey);
            } else {
                $indexItem->set($validSessionIds);
                $this->cachePool->save($indexItem);
            }
        }

        return array_values(array_unique($topics));
    }

    /**
     * @return array{id:string,ownerId:string,topic:string,entityKeys:array<string>,ttlSeconds:int,expiresAt:int}
     *                                                                                                            Example return shape: `['id' => 'ab12...', 'ownerId' => 'user-7', 'entityKeys' => ['App\Entity\Content#42'], ...]`.
     */
    private function getOwnedSession(string $sessionId, string $ownerId): array
    {
        $session = $this->getSession($sessionId);
        if (null === $session) {
            throw new \OutOfBoundsException(sprintf('Mercure session "%s" not found.', $sessionId));
        }
        if ($session['ownerId'] !== $ownerId) {
            throw new \RuntimeException('Mercure session ownership mismatch.');
        }

        return $session;
    }

    /**
     * @param array{id:string,ownerId:string,topic:string,entityKeys:array<string>,ttlSeconds:int,expiresAt:int} $session
     *                                                                                                                    Example input: `['id' => 'ab12...', 'topic' => 'session_ab12...', 'entityKeys' => ['App\Entity\Content#42'], ...]`.
     */
    private function persistSession(array $session): void
    {
        $item = $this->cachePool->getItem($this->getSessionCacheKey($session['id']));
        $item->set($session);
        $item->expiresAfter($session['ttlSeconds']);
        $this->cachePool->save($item);
    }

    /**
     * @param array<string> $entityKeys
     *                                  Example input: `['App\Entity\Content#42', 'App\Entity\Content#84']`
     */
    private function addSessionToIndexes(string $sessionId, array $entityKeys): void
    {
        foreach (array_values(array_unique($entityKeys)) as $entityKey) {
            $indexItem = $this->cachePool->getItem($this->getEntityIndexCacheKey($entityKey));
            $sessionIds = $this->normalizeSessionIdList($indexItem->get());
            if (!in_array($sessionId, $sessionIds, true)) {
                $sessionIds[] = $sessionId;
                $indexItem->set($sessionIds);
                $this->cachePool->save($indexItem);
            }
        }
    }

    /**
     * @param array<string> $entityKeys
     *                                  Example input: `['App\Entity\Content#42', 'App\Entity\Content#84']`
     */
    private function removeSessionFromIndexes(string $sessionId, array $entityKeys): void
    {
        foreach (array_values(array_unique($entityKeys)) as $entityKey) {
            $indexCacheKey = $this->getEntityIndexCacheKey($entityKey);
            $indexItem = $this->cachePool->getItem($indexCacheKey);
            if (!$indexItem->isHit()) {
                continue;
            }

            $sessionIds = array_values(
                array_filter(
                    $this->normalizeSessionIdList($indexItem->get()),
                    static fn (string $indexedSessionId): bool => $indexedSessionId !== $sessionId
                )
            );

            if ([] === $sessionIds) {
                $this->cachePool->deleteItem($indexCacheKey);

                continue;
            }

            $indexItem->set($sessionIds);
            $this->cachePool->save($indexItem);
        }
    }

    private function getSessionCacheKey(string $sessionId): string
    {
        return self::SESSION_CACHE_PREFIX.$sessionId;
    }

    private function getEntityIndexCacheKey(string $entityKey): string
    {
        // Example: `App\Entity\Content#42` becomes `synergy_mercure_session_idx_<sha1>` to keep backend cache keys safe.
        return self::ENTITY_INDEX_CACHE_PREFIX.sha1($entityKey);
    }

    private function normalizeTtl(?int $ttlSeconds): int
    {
        $ttlSeconds ??= $this->defaultTtlSeconds;

        return max(1, $ttlSeconds);
    }

    /**
     * @return array<string>
     *                       Example input/output: `['session_ab12...', '', 12]` becomes `['session_ab12...']`.
     */
    private function normalizeSessionIdList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(
            array_unique(
                array_filter(
                    $value,
                    static fn (mixed $sessionId): bool => is_string($sessionId) && '' !== $sessionId
                )
            )
        );
    }

    /**
     * @return array<string>
     *                       Example input/output: `['App\Entity\Content#42', '', null]` becomes `['App\Entity\Content#42']`
     */
    private function normalizeEntityKeys(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(
            array_unique(
                array_filter(
                    $value,
                    static fn (mixed $entityKey): bool => is_string($entityKey) && '' !== $entityKey
                )
            )
        );
    }
}
