<?php

namespace App\Entity;

use App\Repository\VehicleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Department;
use App\Entity\VehicleType;
use App\Entity\DepartmentVehicleSlot;

#[ORM\Entity(repositoryClass: VehicleRepository::class)]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $vin = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $plate = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $make = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $year = null;

    #[ORM\Column(nullable: true)]
    private ?int $currentOdometerRm = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $currentEngineHours = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /**
     * @var Collection<int, ServiceEvent>
     */
    #[ORM\OneToMany(targetEntity: ServiceEvent::class, mappedBy: 'vehicle')]
    private Collection $serviceEvents;

    #[ORM\ManyToOne(inversedBy: 'vehicles')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Department $department = null;

    public function getDepartment(): ?Department { return $this->department; }
    public function setDepartment(?Department $department): self { $this->department = $department; return $this; }

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?VehicleType $type = null;

    public function getType(): ?VehicleType { return $this->type; }
    public function setType(?VehicleType $type): self { $this->type = $type; return $this; }

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL', unique: true)]
    private ?DepartmentVehicleSlot $staffSlot = null;

    public function getStaffSlot(): ?DepartmentVehicleSlot { return $this->staffSlot; }
    public function setStaffSlot(?DepartmentVehicleSlot $slot): self
    {
        $this->staffSlot = $slot;

        // ✅ Optional: якщо хочеш автопідстановку підрозділу по слоту:
        if ($slot?->getDepartment()) {
            $this->department = $slot->getDepartment();
        }

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVin(): ?string
    {
        return $this->vin;
    }

    public function setVin(?string $vin): static
    {
        $this->vin = $vin;

        return $this;
    }

    public function getPlate(): ?string
    {
        return $this->plate;
    }

    public function setPlate(?string $plate): static
    {
        $this->plate = $plate;

        return $this;
    }

    public function getMake(): ?string
    {
        return $this->make;
    }

    public function setMake(?string $make): static
    {
        $this->make = $make;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getCurrentOdometerRm(): ?int
    {
        return $this->currentOdometerRm;
    }

    public function setCurrentOdometerRm(?int $currentOdometerRm): static
    {
        $this->currentOdometerRm = $currentOdometerRm;

        return $this;
    }

    public function getCurrentEngineHours(): ?string
    {
        return $this->currentEngineHours;
    }

    public function setCurrentEngineHours(?string $currentEngineHours): static
    {
        $this->currentEngineHours = $currentEngineHours;

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

    /**
     * @return Collection<int, ServiceEvent>
     */
    public function getServiceEvents(): Collection
    {
        return $this->serviceEvents;
    }

    public function addServiceEvent(ServiceEvent $serviceEvent): static
    {
        if (!$this->serviceEvents->contains($serviceEvent)) {
            $this->serviceEvents->add($serviceEvent);
            $serviceEvent->setVehicle($this);
        }

        return $this;
    }

    public function removeServiceEvent(ServiceEvent $serviceEvent): static
    {
        if ($this->serviceEvents->removeElement($serviceEvent)) {
            // set the owning side to null (unless already changed)
            if ($serviceEvent->getVehicle() === $this) {
                $serviceEvent->setVehicle(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        $plate = $this->getPlate();
        $make = $this->getMake();
        $model = $this->getModel();

        $label = trim(($make ?? '') . ' ' . ($model ?? ''));
        if ($plate) {
            return $label ? $label . ' (' . $plate . ')' : $plate;
        }

        return $label ?: ('Авто #' . ($this->getId() ?? ''));
    }

    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: \App\Entity\ServicePlan::class, orphanRemoval: true)]
    private Collection $servicePlans;

    public function __construct()
    {
        $this->serviceEvents = new ArrayCollection();
        $this->servicePlans = new ArrayCollection();
    }

    public function getServicePlans(): Collection
    {
        return $this->servicePlans;
    }

    public function addServicePlan(\App\Entity\ServicePlan $plan): self
    {
        if (!$this->servicePlans->contains($plan)) {
            $this->servicePlans->add($plan);
            $plan->setVehicle($this);
        }
        return $this;
    }

    public function removeServicePlan(\App\Entity\ServicePlan $plan): self
    {
        if ($this->servicePlans->removeElement($plan)) {
            if ($plan->getVehicle() === $this) {
                $plan->setVehicle(null);
            }
        }
        return $this;
    }
    public function getOdometerKm(): ?int
    {
        return $this->currentOdometerRm;
    }

    public function setOdometerKm(?int $km): self
    {
        $this->currentOdometerRm = $km;
        return $this;
    }

}


