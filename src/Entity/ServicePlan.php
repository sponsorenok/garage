<?php

// src/Entity/ServicePlan.php

namespace App\Entity;

use App\Repository\ServicePlanRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: ServicePlanRepository::class)]
#[ORM\Table(name: 'service_plan')]
class ServicePlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'servicePlans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Vehicle $vehicle = null;

    #[ORM\Column(length: 255)]
    private string $name;

    // від останнього виконання
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $intervalKm = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $intervalDays = null;

    // від загального пробігу/абсолютних значень
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $absoluteDueOdometerKm = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $absoluteDueAt = null;

    // пороги “скоро”
    #[ORM\Column(type: 'integer')]
    private int $soonKm = 500;

    #[ORM\Column(type: 'integer')]
    private int $soonDays = 30;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    // --- getters/setters (скорочено) ---
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle): self
    {
        $this->vehicle = $vehicle;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getIntervalKm(): ?int
    {
        return $this->intervalKm;
    }

    public function setIntervalKm(?int $v): self
    {
        $this->intervalKm = $v;
        return $this;
    }

    public function getIntervalDays(): ?int
    {
        return $this->intervalDays;
    }

    public function setIntervalDays(?int $v): self
    {
        $this->intervalDays = $v;
        return $this;
    }

    public function getAbsoluteDueOdometerKm(): ?int
    {
        return $this->absoluteDueOdometerKm;
    }

    public function setAbsoluteDueOdometerKm(?int $v): self
    {
        $this->absoluteDueOdometerKm = $v;
        return $this;
    }

    public function getAbsoluteDueAt(): ?\DateTime
    {
        return $this->absoluteDueAt;
    }

    public function setAbsoluteDueAt(?\DateTime $date): self
    {
        $this->absoluteDueAt = $date;
        return $this;
    }

    public function getSoonKm(): int
    {
        return $this->soonKm;
    }

    public function setSoonKm(int $v): self
    {
        $this->soonKm = $v;
        return $this;
    }

    public function getSoonDays(): int
    {
        return $this->soonDays;
    }

    public function setSoonDays(int $v): self
    {
        $this->soonDays = $v;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $v): self
    {
        $this->isActive = $v;
        return $this;
    }

    #[ORM\OneToMany(mappedBy: 'servicePlan', targetEntity: ServicePlanTask::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $tasks;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ServicePlanTemplate $template = null;

    public function getTemplate(): ?ServicePlanTemplate { return $this->template; }
    public function setTemplate(?ServicePlanTemplate $t): self { $this->template = $t; return $this; }

    public function __construct()
    {

        $this->tasks = new ArrayCollection();
    }

    public function getTasks(): Collection { return $this->tasks; }

    public function addTask(ServicePlanTask $t): self {
        if (!$this->tasks->contains($t)) {
            $this->tasks->add($t);
            $t->setServicePlan($this);
        }
        return $this;
    }

    public function removeTask(ServicePlanTask $t): self {
        if ($this->tasks->removeElement($t)) {
            if ($t->getServicePlan() === $this) $t->setServicePlan(null);
        }
        return $this;
    }
}
