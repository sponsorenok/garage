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

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $engineNumber = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $factoryNumber = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $supportService = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $qualityCategory = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $operationGroup = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $initialCost = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Document $passportForm = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $technicalCertificate = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $commissioningOrder = null;

    #[ORM\Column(nullable: true)]
    private ?int $currentOdometerKm = null;

    #[ORM\Column(nullable: true)]
    private ?int $annualMileageKm = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $currentEngineHours = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $receiptDate = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $receivedFrom = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Supplier $supplier = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $receivingDocuments = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $sentTo = null;

    #[ORM\Column(nullable: true)]
    private ?int $batteryRequired = null;

    #[ORM\Column(nullable: true)]
    private ?int $batteryAvailable = null;

    #[ORM\Column(nullable: true)]
    private ?int $tiresRequired = null;

    #[ORM\Column(nullable: true)]
    private ?int $tiresAvailable = null;

    #[ORM\Column(nullable: true)]
    private ?int $maintenanceIntervalKm = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastMaintenanceDate = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $registrationNumber = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $dimensions = null;

    #[ORM\Column(nullable: true)]
    private ?int $grossWeightKg = null;

    #[ORM\Column(nullable: true)]
    private ?int $curbWeightKg = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $fuelConsumptionRates = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /**
     * @var Collection<int, ServiceEvent>
     */
    #[ORM\OneToMany(targetEntity: ServiceEvent::class, mappedBy: 'vehicle')]
    private Collection $serviceEvents;

    /**
     * @var Collection<int, VehicleImage>
     */
    #[ORM\OneToMany(mappedBy: 'vehicle', targetEntity: VehicleImage::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $images;

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

    public function getEngineNumber(): ?string
    {
        return $this->engineNumber;
    }

    public function setEngineNumber(?string $engineNumber): static
    {
        $this->engineNumber = $engineNumber;

        return $this;
    }

    public function getFactoryNumber(): ?string
    {
        return $this->factoryNumber;
    }

    public function setFactoryNumber(?string $factoryNumber): static
    {
        $this->factoryNumber = $factoryNumber;

        return $this;
    }

    public function getSupportService(): ?string
    {
        return $this->supportService;
    }

    public function setSupportService(?string $supportService): static
    {
        $this->supportService = $supportService;

        return $this;
    }

    public function getQualityCategory(): ?string
    {
        return $this->qualityCategory;
    }

    public function setQualityCategory(?string $qualityCategory): static
    {
        $this->qualityCategory = $qualityCategory;

        return $this;
    }

    public function getOperationGroup(): ?string
    {
        return $this->operationGroup;
    }

    public function setOperationGroup(?string $operationGroup): static
    {
        $this->operationGroup = $operationGroup;

        return $this;
    }

    public function getInitialCost(): ?string
    {
        return $this->initialCost;
    }

    public function setInitialCost(?string $initialCost): static
    {
        $this->initialCost = $initialCost;

        return $this;
    }

    public function getPassportForm(): ?Document
    {
        return $this->passportForm;
    }

    public function setPassportForm(?Document $passportForm): static
    {
        $this->passportForm = $passportForm;

        return $this;
    }

    public function getTechnicalCertificate(): ?string
    {
        return $this->technicalCertificate;
    }

    public function setTechnicalCertificate(?string $technicalCertificate): static
    {
        $this->technicalCertificate = $technicalCertificate;

        return $this;
    }

    public function getCommissioningOrder(): ?string
    {
        return $this->commissioningOrder;
    }

    public function setCommissioningOrder(?string $commissioningOrder): static
    {
        $this->commissioningOrder = $commissioningOrder;

        return $this;
    }

    public function getCurrentOdometerKm(): ?int
    {
        return $this->currentOdometerKm;
    }

    public function setCurrentOdometerKm(?int $currentOdometerKm): static
    {
        $this->currentOdometerKm = $currentOdometerKm;

        return $this;
    }

    public function getAnnualMileageKm(): ?int
    {
        return $this->annualMileageKm;
    }

    public function setAnnualMileageKm(?int $annualMileageKm): static
    {
        $this->annualMileageKm = $annualMileageKm;

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

    public function getReceiptDate(): ?\DateTimeInterface
    {
        return $this->receiptDate;
    }

    public function setReceiptDate(?\DateTimeInterface $receiptDate): static
    {
        $this->receiptDate = $receiptDate;

        return $this;
    }

    public function getReceivedFrom(): ?string
    {
        return $this->receivedFrom;
    }

    public function setReceivedFrom(?string $receivedFrom): static
    {
        $this->receivedFrom = $receivedFrom;

        return $this;
    }

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getReceivingDocuments(): ?string
    {
        return $this->receivingDocuments;
    }

    public function setReceivingDocuments(?string $receivingDocuments): static
    {
        $this->receivingDocuments = $receivingDocuments;

        return $this;
    }

    public function getSentTo(): ?string
    {
        return $this->sentTo;
    }

    public function setSentTo(?string $sentTo): static
    {
        $this->sentTo = $sentTo;

        return $this;
    }

    public function getBatteryRequired(): ?int
    {
        return $this->batteryRequired;
    }

    public function setBatteryRequired(?int $batteryRequired): static
    {
        $this->batteryRequired = $batteryRequired;

        return $this;
    }

    public function getBatteryAvailable(): ?int
    {
        return $this->batteryAvailable;
    }

    public function setBatteryAvailable(?int $batteryAvailable): static
    {
        $this->batteryAvailable = $batteryAvailable;

        return $this;
    }

    public function getTiresRequired(): ?int
    {
        return $this->tiresRequired;
    }

    public function setTiresRequired(?int $tiresRequired): static
    {
        $this->tiresRequired = $tiresRequired;

        return $this;
    }

    public function getTiresAvailable(): ?int
    {
        return $this->tiresAvailable;
    }

    public function setTiresAvailable(?int $tiresAvailable): static
    {
        $this->tiresAvailable = $tiresAvailable;

        return $this;
    }

    public function getMaintenanceIntervalKm(): ?int
    {
        return $this->maintenanceIntervalKm;
    }

    public function setMaintenanceIntervalKm(?int $maintenanceIntervalKm): static
    {
        $this->maintenanceIntervalKm = $maintenanceIntervalKm;

        return $this;
    }

    public function getLastMaintenanceDate(): ?\DateTimeInterface
    {
        return $this->lastMaintenanceDate;
    }

    public function setLastMaintenanceDate(?\DateTimeInterface $lastMaintenanceDate): static
    {
        $this->lastMaintenanceDate = $lastMaintenanceDate;

        return $this;
    }

    public function getRegistrationNumber(): ?string
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(?string $registrationNumber): static
    {
        $this->registrationNumber = $registrationNumber;

        return $this;
    }

    public function getDimensions(): ?string
    {
        return $this->dimensions;
    }

    public function setDimensions(?string $dimensions): static
    {
        $this->dimensions = $dimensions;

        return $this;
    }

    public function getGrossWeightKg(): ?int
    {
        return $this->grossWeightKg;
    }

    public function setGrossWeightKg(?int $grossWeightKg): static
    {
        $this->grossWeightKg = $grossWeightKg;

        return $this;
    }

    public function getCurbWeightKg(): ?int
    {
        return $this->curbWeightKg;
    }

    public function setCurbWeightKg(?int $curbWeightKg): static
    {
        $this->curbWeightKg = $curbWeightKg;

        return $this;
    }

    public function getFuelConsumptionRates(): ?string
    {
        return $this->fuelConsumptionRates;
    }

    public function setFuelConsumptionRates(?string $fuelConsumptionRates): static
    {
        $this->fuelConsumptionRates = $fuelConsumptionRates;

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

    /**
     * @return Collection<int, VehicleImage>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(VehicleImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setVehicle($this);
        }

        return $this;
    }

    public function removeImage(VehicleImage $image): static
    {
        if ($this->images->removeElement($image)) {
            if ($image->getVehicle() === $this) {
                $image->setVehicle(null);
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
        $this->images = new ArrayCollection();
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
        return $this->currentOdometerKm;
    }

    public function setOdometerKm(?int $km): self
    {
        $this->currentOdometerKm = $km;
        return $this;
    }

}

