<?php

namespace Efrogg\Synergy\Mercure;

class MercureActionCollection
{
    /**
     * @param array<EntityAction> $actions
     */
    public function __construct(
        private array $actions = []
    ) {
    }

    public function addAction(EntityAction $action): self
    {
        $this->actions[] = $action;
        return $this;
    }

    /**
     * @return array<EntityAction>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

}
