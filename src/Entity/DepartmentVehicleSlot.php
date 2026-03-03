<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'dept_vehicle_slot')]
class DepartmentVehicleSlot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'staffSlots')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Department $department = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?VehicleType $type = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $brand = null; // "MAN"

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null; // "Вантажна #2"

    public function getId(): ?int { return $this->id; }

    public function getDepartment(): ?Department { return $this->department; }
    public function setDepartment(?Department $d): self { $this->department = $d; return $this; }

    public function getType(): ?VehicleType { return $this->type; }
    public function setType(?VehicleType $t): self { $this->type = $t; return $this; }

    public function getBrand(): ?string { return $this->brand; }
    public function setBrand(?string $b): self { $this->brand = $b; return $this; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $v): self { $this->title = $v; return $this; }

    public function __toString(): string
    {
        $parts = [];
        if ($this->type) $parts[] = (string)$this->type;
        if ($this->brand) $parts[] = $this->brand;
        if ($this->title) $parts[] = $this->title;

        return $parts ? implode(' / ', $parts) : ('Slot #'.$this->id);
    }
}
