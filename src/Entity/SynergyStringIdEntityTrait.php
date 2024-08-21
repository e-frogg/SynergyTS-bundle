<?php

namespace Efrogg\Synergy\Entity;

use Doctrine\ORM\Mapping as ORM;

trait SynergyStringIdEntityTrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?string $id = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string|int $id): static
    {
        if(!is_string($id)) {
            throw new \InvalidArgumentException('Id must be a string');
        }
        $this->id = $id;

        return $this;
    }
}
