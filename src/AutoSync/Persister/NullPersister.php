<?php

namespace Efrogg\Synergy\AutoSync\Persister;

use Efrogg\Synergy\AutoSync\AutoSync;

class NullPersister implements AutoSyncPersisterInterface
{

    public function persist(AutoSync $autoSync): void
    {
        throw new \Exception('Not implemented');
    }

    public function getAutoSyncs(): array
    {
        throw new \Exception('Not implemented');
    }

    public function cleanExpired(): void
    {
        throw new \Exception('Not implemented');
    }
}
