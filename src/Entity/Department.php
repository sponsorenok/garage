<?php

namespace App\Entity;

use App\Repository\DepartmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepartmentRepository::class)]
#[ORM\Table(name: 'department')]
class Department
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $name;

    #[ORM\Column(length: 50, nullable: true, unique: true)]
    private ?string $code = null;

    // ---- hierarchy ----
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $children;

    // ---- vehicles in department (optional, your old approach) ----
    #[ORM\OneToMany(mappedBy: 'department', targetEntity: Vehicle::class)]
    private Collection $vehicles;

    // ---- staffing slots ----
    #[ORM\OneToMany(mappedBy: 'department', targetEntity: DepartmentVehicleSlot::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $staffSlots;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->vehicles = new ArrayCollection();
        $this->staffSlots = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getCode(): ?string { return $this->code; }
    public function setCode(?string $code): self { $this->code = $code; return $this; }

    public function getParent(): ?self { return $this->parent; }
    public function setParent(?self $parent): self { $this->parent = $parent; return $this; }

    /** @return Collection<int, self> */
    public function getChildren(): Collection { return $this->children; }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }
        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->removeElement($child)) {
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Vehicle> */
    public function getVehicles(): Collection { return $this->vehicles; }

    /** @return Collection<int, DepartmentVehicleSlot> */
    public function getStaffSlots(): Collection { return $this->staffSlots; }

    public function addStaffSlot(DepartmentVehicleSlot $slot): self
    {
        if (!$this->staffSlots->contains($slot)) {
            $this->staffSlots->add($slot);
            $slot->setDepartment($this);
        }
        return $this;
    }

    public function removeStaffSlot(DepartmentVehicleSlot $slot): self
    {
        if ($this->staffSlots->removeElement($slot)) {
            if ($slot->getDepartment() === $this) {
                $slot->setDepartment(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->code ? "{$this->name} ({$this->code})" : $this->name;
    }

    // src/Entity/Department.php

    public function getDepth(): int
    {
        $depth = 0;
        $p = $this->getParent();

        while ($p !== null) {
            $depth++;
            $p = $p->getParent();

            if ($depth > 50) break; // safety for broken loops
        }

        return $depth;
    }

    public function getTreeName(): string
    {
        return str_repeat('— ', $this->getDepth()) . $this->getName();
    }

}
