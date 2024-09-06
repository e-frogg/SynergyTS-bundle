<?php

namespace Efrogg\Synergy\Mercure;

use Countable;

class MercureActionCollection implements Countable
{
    /**
     * @param array<EntityAction> $actions
     */
    public function __construct(
        private array $actions = []
    ) {
    }

    public function count(): int
    {
        return count($this->actions);
    }

    public function addAction(EntityAction $action, bool $deduplicate = true): self
    {
        if ($deduplicate) {
            foreach ($this->actions as $existingAction) {
                if ($existingAction->isSame($action)) {
                    $existingAction->merge($action);
                    return $this;
                }
            }
        }
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
