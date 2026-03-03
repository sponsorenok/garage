<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'service_plan_template')]
class ServicePlanTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    // як у ServicePlan
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $intervalKm = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $intervalDays = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $absoluteDueOdometerKm = null;

    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $absoluteDueAt = null;

    #[ORM\Column(type: 'integer')]
    private int $soonKm = 500;

    #[ORM\Column(type: 'integer')]
    private int $soonDays = 30;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\OneToMany(mappedBy: 'template', targetEntity: ServicePlanTemplateTask::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $tasks;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function __toString(): string { return $this->name; }

    public function getName(): string { return $this->name; }
    public function setName(string $v): self { $this->name = $v; return $this; }

    public function getIntervalKm(): ?int { return $this->intervalKm; }
    public function setIntervalKm(?int $v): self { $this->intervalKm = $v; return $this; }

    public function getIntervalDays(): ?int { return $this->intervalDays; }
    public function setIntervalDays(?int $v): self { $this->intervalDays = $v; return $this; }

    public function getAbsoluteDueOdometerKm(): ?int { return $this->absoluteDueOdometerKm; }
    public function setAbsoluteDueOdometerKm(?int $v): self { $this->absoluteDueOdometerKm = $v; return $this; }

    public function getAbsoluteDueAt(): ?\DateTime { return $this->absoluteDueAt; }
    public function setAbsoluteDueAt(?\DateTime $v): self { $this->absoluteDueAt = $v; return $this; }

    public function getSoonKm(): int { return $this->soonKm; }
    public function setSoonKm(int $v): self { $this->soonKm = $v; return $this; }

    public function getSoonDays(): int { return $this->soonDays; }
    public function setSoonDays(int $v): self { $this->soonDays = $v; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $v): self { $this->isActive = $v; return $this; }

    public function getTasks(): Collection { return $this->tasks; }

    public function addTask(ServicePlanTemplateTask $t): self
    {
        if (!$this->tasks->contains($t)) {
            $this->tasks->add($t);
            $t->setTemplate($this);
        }
        return $this;
    }

    public function removeTask(ServicePlanTemplateTask $t): self
    {
        if ($this->tasks->removeElement($t)) {
            if ($t->getTemplate() === $this) $t->setTemplate(null);
        }
        return $this;
    }
}
