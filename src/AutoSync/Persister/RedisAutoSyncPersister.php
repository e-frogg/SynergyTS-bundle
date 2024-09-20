<?php

namespace Efrogg\Synergy\AutoSync\Persister;

use Efrogg\Synergy\AutoSync\AutoSync;
use Redis;
use RedisException;

class RedisAutoSyncPersister implements AutoSyncPersisterInterface
{
    public function __construct(
        private readonly Redis $redis
    )
    {
    }

    public function persist(AutoSync $autoSync): void
    {
        $cacheKey = 'autosync-'.$autoSync->getId();
        $this->redis->set($cacheKey, serialize($autoSync), $autoSync->getTtl());
    }

    /**
     * @return array<AutoSync>
     * @throws RedisException
     */
    public function getAutoSyncs(): array
    {
        $autoSyncs = [];
        $keys = $this->redis->keys('autosync-*');
        foreach ($keys as $key) {
            $autoSyncs[] = unserialize($this->redis->get($key),['allowed_classes' => [AutoSync::class]]);
        }
        return $autoSyncs;
    }

    public function cleanExpired(): void
    {
        // TODO: Implement cleanExpired() method.
    }
}
