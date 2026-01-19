<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Mercure;

class EntityFullUpdateAction extends EntityAction
{
    protected static string $action = 'inject';
    /** @var array<string,mixed> */
    protected static array $additionalParameters = [
        'fullUpdate' => true,
    ];
}
