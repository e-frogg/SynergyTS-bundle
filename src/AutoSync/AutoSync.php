<?php

namespace Efrogg\Synergy\AutoSync;

use Efrogg\Synergy\Data\Criteria;

class AutoSync
{


    /**
     * @var array<string, Criteria>
     *     indexed on entity name
     */
    private array $criteriaCollection=[];

    /**
     * @var int
     *  Time To Lives in seconds
     */
    private int $ttl = 0;


    public function __construct(
        private readonly string $id
    )
    {
    }

    /**
     * @param int $ttl
     */
    public function setTtl(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    /**
     * @return int
     *  Time To Lives in seconds
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTopic(): string
    {
        return $this->id;
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
