<?php

namespace Efrogg\Synergy\AutoSync;

use Efrogg\Synergy\Data\Criteria;

class AutoSync
{

    /**
     * @var array<string>
     */
    private array $topics=[];

    /**
     * @var array<string, Criteria>
     *     indexed on entity name
     */
    private array $criteriaCollection=[];

    /**
     * @return string[]
     */
    public function getTopic(): array
    {
        return $this->topics;
    }

    public function addTopic(string $topic): self
    {
        $this->topics[] = $topic;
        return $this;
    }

    /**
     * @param array<string,Criteria> $criteriaCollection
     */
    public function setCriteriaCollection(array $criteriaCollection): void
    {
        $this->criteriaCollection = $criteriaCollection;
    }

    /**
     * @return array
     */
    public function getCriteriaCollection(): array
    {
        return $this->criteriaCollection;
    }

}
