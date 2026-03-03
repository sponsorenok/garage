<?php

namespace App\Entity;

use App\Domain\Number\Decimal;
use App\Repository\PurchaseLineRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
#[ORM\Entity(repositoryClass: PurchaseLineRepository::class)]
class PurchaseLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Purchase $purchase = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Item $item = null;

    #[ORM\ManyToOne]
    private ?Warehouse $warehouse = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 3)]
    private string $qty = '0.000';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $unitCost = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $lotCode = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $expiryDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /** @var Collection<int, PurchaseLineRequestItemAllocation> */
    #[ORM\OneToMany(targetEntity: PurchaseLineRequestItemAllocation::class, mappedBy: 'purchaseLine', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $allocations;

    #[ORM\Column(length: 20, options: ['default' => 'MANUAL'])]
    private string $sourceType = 'MANUAL'; // MANUAL | REQUEST

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $requestItemId = null; // PartRequestItem.id

    public function getSourceType(): string { return $this->sourceType; }
    public function setSourceType(string $t): self { $this->sourceType = $t; return $this; }

    public function getRequestItemId(): ?int { return $this->requestItemId; }
    public function setRequestItemId(?int $id): self { $this->requestItemId = $id; return $this; }

    public function getAllocations(): Collection
{
    return $this->allocations;
}
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPurchase(): ?Purchase
    {
        return $this->purchase;
    }

    public function setPurchase(?Purchase $purchase): static
    {
        $this->purchase = $purchase;

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

    public function getWarehouse(): ?Warehouse
    {
        return $this->warehouse;
    }

    public function setWarehouse(?Warehouse $warehouse): static
    {
        $this->warehouse = $warehouse;

        return $this;
    }

    public function getQty(): ?string
    {
        return $this->qty;
    }

    public function setQty(string $qty): static
    {
        $this->qty = Decimal::normalize($qty, 3);
        return $this;
    }

    public function getUnitCost(): ?string
    {
        return $this->unitCost;
    }

    public function setUnitCost(?string $unitCost): static
    {
        $this->unitCost = $unitCost;

        return $this;
    }

    public function getLotCode(): ?string
    {
        return $this->lotCode;
    }

    public function setLotCode(?string $lotCode): static
    {
        $this->lotCode = $lotCode;

        return $this;
    }

    public function getExpiryDate(): ?\DateTime
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(?\DateTime $expiryDate): static
    {
        $this->expiryDate = $expiryDate;

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

    public function addAllocation(PurchaseLineRequestItemAllocation $a): self
    {
        if (!$this->allocations->contains($a)) {
            $this->allocations->add($a);
            $a->setPurchaseLine($this);
        }
        return $this;
    }

    public function removeAllocation(PurchaseLineRequestItemAllocation $a): self
    {
        if ($this->allocations->removeElement($a)) {
            if ($a->getPurchaseLine() === $this) {
                $a->setPurchaseLine(null);
            }
        }
        return $this;
    }


    public function __toString(): string
    {
        $name = $this->getItem()?->getName() ?? 'Позиція';
        $qty = (string) $this->getQty();
        return $name . ' — ' . $qty;
    }

    public function __construct()
    {
        $this->allocations = new ArrayCollection();
    }



}
