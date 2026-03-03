<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: \App\Repository\VehicleAssignmentRepository::class)]
#[ORM\Table(name: 'vehicle_assignment')]
#[ORM\Index(columns: ['is_active'], name: 'idx_va_is_active')]
class VehicleAssignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Vehicle $vehicle = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Department $department = null;

    // може бути null: авто просто стоїть у підрозділі без слота
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?DepartmentVehicleSlot $staffSlot = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    /** @var Collection<int, Document> */
    #[ORM\OneToMany(mappedBy: 'assignment', targetEntity: Document::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $documents;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->documents = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getVehicle(): ?Vehicle { return $this->vehicle; }
    public function setVehicle(?Vehicle $vehicle): self { $this->vehicle = $vehicle; return $this; }

    public function getDepartment(): ?Department { return $this->department; }
    public function setDepartment(?Department $department): self { $this->department = $department; return $this; }

    public function getStaffSlot(): ?DepartmentVehicleSlot { return $this->staffSlot; }
    public function setStaffSlot(?DepartmentVehicleSlot $slot): self { $this->staffSlot = $slot; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $dt): self { $this->createdAt = $dt; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $active): self { $this->isActive = $active; return $this; }

    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): self { $this->note = $note; return $this; }

    /** @return Collection<int, Document> */
    public function getDocuments(): Collection { return $this->documents; }

    public function addDocument(Document $doc): self
    {
        if (!$this->documents->contains($doc)) {
            $this->documents->add($doc);
            $doc->setAssignment($this);
        }
        return $this;
    }

    public function removeDocument(Document $doc): self
    {
        if ($this->documents->removeElement($doc)) {
            if ($doc->getAssignment() === $this) {
                $doc->setAssignment(null); // (але в нас nullable false у doc, тож фактично видалятиметься doc)
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        $v = $this->vehicle ? (string)$this->vehicle : 'Авто';
        $d = $this->department ? (string)$this->department : 'Підрозділ';
        $s = $this->staffSlot ? (' / '.$this->staffSlot) : '';
        return $v.' → '.$d.$s;
    }
}
