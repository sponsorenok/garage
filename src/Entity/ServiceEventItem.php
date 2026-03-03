<?php

namespace App\Entity;

use App\Repository\ServiceEventItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceEventItemRepository::class)]
class ServiceEventItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ServiceEvent $serviceEvent = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Warehouse $warehouse = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Item $item = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 3)]
    private ?string $qtyUsed = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $unitCostSnapshot = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServiceEvent(): ?ServiceEvent
    {
        return $this->serviceEvent;
    }

    public function setServiceEvent(?ServiceEvent $serviceEvent): static
    {
        $this->serviceEvent = $serviceEvent;

        return $this;
    }

    public function getWarehouse(): ?Warehouse
    {
        return $this->warehouse;
    }

    public function setWarehouse(?Warehouse $warehouse): static
    {
        $this->warehouse = $warehouse;

        return $this;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): static
    {
        $this->item = $item;

        return $this;
    }

    public function getQtyUsed(): ?string
    {
        return $this->qtyUsed;
    }

    public function setQtyUsed(string $qtyUsed): static
    {
        $this->qtyUsed = $qtyUsed;

        return $this;
    }

    public function getUnitCostSnapshot(): ?string
    {
        return $this->unitCostSnapshot;
    }

    public function setUnitCostSnapshot(?string $unitCostSnapshot): static
    {
        $this->unitCostSnapshot = $unitCostSnapshot;

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
    public function __toString(): string
    {
        $itemName = $this->getItem()?->getName();
        $qty = $this->getQtyUsed();
        $unit = $this->getItem()?->getUnit(); // pcs/liter/kg

        $unitUa = match ($unit) {
            'pcs' => 'шт',
            'liter' => 'л',
            'kg' => 'кг',
            default => $unit ?? '',
        };

        $qtyStr = $qty !== null ? rtrim(rtrim(number_format((float)$qty, 3, '.', ''), '0'), '.') : '';

        // Напр: "Масло 5W-30 — 4 л"
        if ($itemName && $qtyStr) {
            return sprintf('%s — %s %s', $itemName, $qtyStr, $unitUa);
        }

        return $itemName ?: 'Позиція ТО';
    }
}
