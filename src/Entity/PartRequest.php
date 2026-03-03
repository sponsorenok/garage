<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'part_request')]
class PartRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Department $department = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 20)]
    private string $status = 'DRAFT';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    /** @var Collection<int, PartRequestItem> */
    #[ORM\OneToMany(mappedBy: 'request', targetEntity: PartRequestItem::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['lineNo' => 'ASC', 'id' => 'ASC'])]
    private Collection $items;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Vehicle $defaultVehicle = null;

    public function getDefaultVehicle(): ?Vehicle { return $this->defaultVehicle; }
    public function setDefaultVehicle(?Vehicle $v): self { $this->defaultVehicle = $v; return $this; }


    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getDepartment(): ?Department { return $this->department; }
    public function setDepartment(?Department $department): self { $this->department = $department; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $dt): self { $this->createdAt = $dt; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): self { $this->note = $note; return $this; }

    /** @return Collection<int, PartRequestItem> */
    public function getItems(): Collection { return $this->items; }

    public function addItem(PartRequestItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setRequest($this);
        }
        return $this;
    }

    public function removeItem(PartRequestItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getRequest() === $this) {
                $item->setRequest(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        $dept = $this->department ? (string)$this->department : 'Підрозділ';
        return sprintf('Заявка #%d (%s)', $this->id ?? 0, $dept);
    }

    public function getVehiclesSummary(): string
    {
        $names = [];
        foreach ($this->getItems() as $it) {
            $v = $it->getVehicle();
            if ($v && $v->getId()) {
                $names[$v->getId()] = (string)$v; // унікально
            }
        }
        if (!$names) return '—';
        return implode(', ', array_values($names));
    }
}
