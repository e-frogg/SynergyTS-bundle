<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Event;

use Efrogg\Synergy\Entity\SynergyEntityInterface;

class AclEntityGrantEvent extends AclGrantEvent
{

    /**
     * @param array<string> $violations
     */
    public function __construct(
        private readonly SynergyEntityInterface $entity,
        string $action,
        bool $granted,
        array $violations = [],
    ) {
        parent::__construct($action, $granted, $violations);
    }

    public function getEntity(): SynergyEntityInterface
    {
        return $this->entity;
    }

}
