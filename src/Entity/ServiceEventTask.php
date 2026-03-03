<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'service_event_task')]
class ServiceEventTask
{
    public const STATUS_PLANNED = 'PLANNED';
    public const STATUS_DONE    = 'DONE';
    public const STATUS_SKIPPED = 'SKIPPED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ServiceEvent $serviceEvent = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?ServicePlanTask $planTask = null;

    // snapshot
    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(type:'integer', nullable: true)]
    private ?int $intervalKm = null;

    #[ORM\Column(type:'integer', nullable: true)]
    private ?int $intervalDays = null;

    #[ORM\Column(type:'integer')]
    private int $soonKm = 500;

    #[ORM\Column(type:'integer')]
    private int $soonDays = 30;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PLANNED;

    #[ORM\Column(type:'integer', nullable: true)]
    private ?int $doneOdometerKm = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $doneDate = null;

    // -------------------------
    // Basic getters/setters
    // -------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServiceEvent(): ?ServiceEvent
    {
        return $this->serviceEvent;
    }

    public function setServiceEvent(?ServiceEvent $e): self
    {
        $this->serviceEvent = $e;
        return $this;
    }

    public function getPlanTask(): ?ServicePlanTask
    {
        return $this->planTask;
    }

    public function setPlanTask(?ServicePlanTask $t): self
    {
        $this->planTask = $t;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $v): self
    {
        $this->name = $v;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * ✅ Автоматично проставляємо doneDate/doneOdometerKm, якщо треба
     * (doneOdometerKm краще проставляти в listener або при сабміті форми, коли відомий пробіг)
     */
    public function setStatus(string $v): self
    {
        $this->status = $v;

        if ($v === self::STATUS_DONE) {
            // дата виконання: беремо з ServiceEvent, інакше today
            if ($this->doneDate === null) {
                $this->doneDate = $this->serviceEvent?->getServiceDate() ?? new \DateTime('today');
            }
            // пробіг виконання: беремо з ServiceEvent
            if ($this->doneOdometerKm === null) {
                $this->doneOdometerKm = $this->serviceEvent?->getOdometerKm();
            }
        } else {
            // якщо повернули назад з DONE — чистимо
            $this->doneDate = null;
            $this->doneOdometerKm = null;
        }

        return $this;
    }


    public function getDoneOdometerKm(): ?int
    {
        return $this->doneOdometerKm;
    }

    public function setDoneOdometerKm(?int $v): self
    {
        $this->doneOdometerKm = $v;
        return $this;
    }

    public function getDoneDate(): ?\DateTime
    {
        return $this->doneDate;
    }

    public function setDoneDate(?\DateTime $v): self
    {
        $this->doneDate = $v;
        return $this;
    }

    // -------------------------
    // Helpers
    // -------------------------

    public function isDone(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    public function isPlanned(): bool
    {
        return $this->status === self::STATUS_PLANNED;
    }

    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }

    public function markDone(?\DateTime $date = null, ?int $odometerKm = null): self
    {
        $this->status = self::STATUS_DONE;
        $this->doneDate = $date ?? new \DateTime('today');
        $this->doneOdometerKm = $odometerKm;
        return $this;
    }

    public function unmarkDone(): self
    {
        $this->status = self::STATUS_PLANNED;
        $this->doneDate = null;
        $this->doneOdometerKm = null;
        return $this;
    }
}
