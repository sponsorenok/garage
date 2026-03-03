<?php

namespace App\Entity;

use App\Domain\Number\Decimal;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'purchase_line_req_item_alloc')]
#[ORM\Index(columns: ['purchase_line_id'], name: 'idx_alloc_line')]
#[ORM\Index(columns: ['request_item_id'], name: 'idx_alloc_req_item')]
class PurchaseLineRequestItemAllocation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'allocations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PurchaseLine $purchaseLine = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PartRequestItem $requestItem = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 3)]
    private string $qty = '0.000';

    public function getId(): ?int { return $this->id; }

    public function getPurchaseLine(): ?PurchaseLine { return $this->purchaseLine; }
    public function setPurchaseLine(?PurchaseLine $l): self { $this->purchaseLine = $l; return $this; }

    public function getRequestItem(): ?PartRequestItem { return $this->requestItem; }
    public function setRequestItem(?PartRequestItem $it): self { $this->requestItem = $it; return $this; }

    public function getQty(): string { return $this->qty; }
    public function setQty(string $qty): self
    {
        $this->qty = Decimal::normalize($qty, 3);
        return $this;
    }

    public function __toString(): string
    {
        $req = $this->requestItem?->getRequest()?->getId();
        $line = $this->requestItem?->getLineNo();
        $name = $this->requestItem?->getNameRaw();
        return sprintf('#%s/%s %s — %s', $req ?? '—', $line ?? '—', $name ?? 'Позиція', $this->qty);
    }
}
