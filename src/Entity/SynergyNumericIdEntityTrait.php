<?php

namespace Efrogg\Synergy\Entity;

use Doctrine\ORM\Mapping as ORM;

trait SynergyNumericIdEntityTrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(string|int $id): static
    {
        if (is_string($id)) {
            throw new \InvalidArgumentException('Id must be an integer');
        }
        $this->id = $id;

        return $this;
    }
}
