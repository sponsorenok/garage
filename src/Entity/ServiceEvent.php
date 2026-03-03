<?php

namespace App\Entity;

use App\Repository\ServiceEventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceEventRepository::class)]
class ServiceEvent
{
    public const STATUS_OPEN = 'OPEN';
    public const STATUS_CLOSED = 'CLOSED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'serviceEvents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Vehicle $vehicle = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $serviceDate = null;

    #[ORM\Column]
    private ?int $odometerKm = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $engineHours = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $laborCost = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $otherCost = null;

    // ✅ Статус події ТО (для “частково виконаного плану”)
    #[ORM\Column(length: 10)]
    private string $status = self::STATUS_OPEN;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?ServicePlan $servicePlan = null;

    #[ORM\OneToMany(mappedBy: 'serviceEvent', targetEntity: ServiceEventItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    #[ORM\OneToMany(mappedBy: 'serviceEvent', targetEntity: ServiceEventTask::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $tasks;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->tasks = new ArrayCollection();
        $this->status = self::STATUS_OPEN;
    }

    // -------------------------
    // Basic getters/setters
    // -------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle): static
    {
        $this->vehicle = $vehicle;
        return $this;
    }

    public function getServiceDate(): ?\DateTime
    {
        return $this->serviceDate;
    }

    public function setServiceDate(\DateTime $serviceDate): static
    {
        $this->serviceDate = $serviceDate;
        return $this;
    }

    public function getOdometerKm(): ?int
    {
        return $this->odometerKm;
    }

    public function setOdometerKm(int $odometerKm): static
    {
        $this->odometerKm = $odometerKm;
        return $this;
    }

    public function getEngineHours(): ?string
    {
        return $this->engineHours;
    }

    public function setEngineHours(?string $engineHours): static
    {
        $this->engineHours = $engineHours;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getLaborCost(): ?string
    {
        return $this->laborCost;
    }

    public function setLaborCost(?string $laborCost): static
    {
        $this->laborCost = $laborCost;
        return $this;
    }

    public function getOtherCost(): ?string
    {
        return $this->otherCost;
    }

    public function setOtherCost(?string $otherCost): static
    {
        $this->otherCost = $otherCost;
        return $this;
    }

    // -------------------------
    // Status (OPEN/CLOSED)
    // -------------------------

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        // можна додати валідацію якщо хочеш
        $this->status = $status;
        return $this;
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function close(?\DateTimeImmutable $when = null): void
    {
        $this->status = self::STATUS_CLOSED;
        $this->closedAt = $when ?? new \DateTimeImmutable();
    }

    public function reopen(): void
    {
        $this->status = self::STATUS_OPEN;
        $this->closedAt = null;
    }

    // -------------------------
    // Plan relation
    // -------------------------

    public function getServicePlan(): ?ServicePlan
    {
        return $this->servicePlan;
    }

    public function setServicePlan(?ServicePlan $plan): self
    {
        $this->servicePlan = $plan;
        return $this;
    }

    // -------------------------
    // Items
    // -------------------------

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(ServiceEventItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setServiceEvent($this);
        }
        return $this;
    }

    public function removeItem(ServiceEventItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getServiceEvent() === $this) {
                $item->setServiceEvent(null);
            }
        }
        return $this;
    }

    // -------------------------
    // Tasks
    // -------------------------

    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(ServiceEventTask $t): self
    {
        if (!$this->tasks->contains($t)) {
            $this->tasks->add($t);
            $t->setServiceEvent($this);
        }
        return $this;
    }

    public function removeTask(ServiceEventTask $t): self
    {
        if ($this->tasks->removeElement($t)) {
            if ($t->getServiceEvent() === $this) {
                $t->setServiceEvent(null);
            }
        }
        return $this;
    }

    /**
     * ✅ Прогрес виконання плану в межах цього ServiceEvent
     * DONE / (DONE + PLANNED) (SKIPPED можна або рахувати як done, або ігнорувати — тут ігноруємо)
     */
    public function getTasksProgress(bool $ignoreSkipped = true): array
    {
        $total = 0;
        $done = 0;

        foreach ($this->getTasks() as $t) {
            if ($ignoreSkipped && $t->getStatus() === ServiceEventTask::STATUS_SKIPPED) continue;
            $total++;
            if ($t->getStatus() === ServiceEventTask::STATUS_DONE) $done++;
        }

        return [
            'done' => $done,
            'total' => $total,
            'ratio' => $total > 0 ? $done / $total : null,
            'text' => $total > 0 ? ($done.'/'.$total) : '—',
            'isAllDone' => $total > 0 && $done === $total,
            'isStarted' => $done > 0,
        ];
    }
    public function getTasksProgressText(): string
    {
        $p = $this->getTasksProgress();
        return $p['text'];
    }
}
