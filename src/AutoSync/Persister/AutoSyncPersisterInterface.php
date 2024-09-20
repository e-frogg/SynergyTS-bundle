<?php

namespace Efrogg\Synergy\AutoSync\Persister;

use Efrogg\Synergy\AutoSync\AutoSync;

interface AutoSyncPersisterInterface
{
    public function persist(AutoSync $autoSync): void;

    /**
     * @return array<AutoSync>
     */
    public function getAutoSyncs(): array;

    public function cleanExpired(): void;
}
