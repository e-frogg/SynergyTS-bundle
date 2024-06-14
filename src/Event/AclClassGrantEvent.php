<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Event;

class AclClassGrantEvent extends AclGrantEvent
{


    /**
     * @param array<string> $violations
     */
    public function __construct(
        private readonly string $entityClass,
        string $action,
        bool $granted,
        array $violations = [],
    ) {
        parent::__construct($action, $granted, $violations);
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }
}
