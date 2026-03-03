<?php

namespace App\Entity;

use App\Repository\PurchaseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
#[ORM\Entity(repositoryClass: PurchaseRepository::class)]
class Purchase
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    private ?Supplier $supplier = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Warehouse $warehouse = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $purchaseDate = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $invoiceNumber = null;

    #[ORM\Column(length: 20)]
    private ?string $currency = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /** @var Collection<int, PartRequest> */
    #[ORM\ManyToMany(targetEntity: PartRequest::class)]
    #[ORM\JoinTable(name: 'purchase_part_request')]
    private Collection $requests;

    /** @var Collection<int, Document> */
    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'purchase', cascade: ['persist'], orphanRemoval: true)]
    private Collection $documents;

    #[ORM\Column(length: 20)]
    private string $status = 'DRAFT';

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $s): self { $this->status = $s; return $this; }


    public function getId(): ?int
    {
        return $this->id;
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

    public function getWarehouse(): ?Warehouse
    {
        return $this->warehouse;
    }

    public function setWarehouse(?Warehouse $warehouse): static
    {
        $this->warehouse = $warehouse;

        return $this;
    }

    public function getPurchaseDate(): ?\DateTime
    {
        return $this->purchaseDate;
    }

    public function setPurchaseDate(\DateTime $purchaseDate): static
    {
        $this->purchaseDate = $purchaseDate;

        return $this;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(?string $invoiceNumber): static
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

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

    #[ORM\OneToMany(targetEntity: PurchaseLine::class, mappedBy: 'purchase', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lines;

    public function getRequests(): Collection
    {
        return $this->requests;
    }

    public function addRequest(PartRequest $r): self
    {
        if (!$this->requests->contains($r)) {
            $this->requests->add($r);
        }
        return $this;
    }

    public function removeRequest(PartRequest $r): self
    {
        $this->requests->removeElement($r);
        return $this;
    }

    public function getDocuments(): Collection
    {
        return $this->documents;
    }
    public function addDocument(Document $doc): self
    {
        if (!$this->documents->contains($doc)) {
            $this->documents->add($doc);
            $doc->setPurchase($this);
        }
        return $this;
    }

    public function removeDocument(Document $doc): self
    {
        if ($this->documents->removeElement($doc)) {
            if ($doc->getPurchase() === $this) $doc->setPurchase(null);
        }
        return $this;
    }



    public function __construct()
    {
        $this->lines = new ArrayCollection();
        $this->requests = new ArrayCollection();
        $this->documents = new ArrayCollection();
    }

    public function getLines(): Collection
    {
        return $this->lines;
    }
    public function addLine(PurchaseLine $line): self
    {
        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
            $line->setPurchase($this);
        }
        return $this;
    }

    public function removeLine(PurchaseLine $line): self
    {
        if ($this->lines->removeElement($line)) {
            if ($line->getPurchase() === $this) {
                $line->setPurchase(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        $d = $this->getPurchaseDate()?->format('Y-m-d') ?? '';
        $inv = $this->getInvoiceNumber() ? (' №'.$this->getInvoiceNumber()) : '';
        return 'Закупівля '.$d.$inv;
    }

    private ?string $linesSpreadsheet = null;
    public function getLinesSpreadsheet(): ?string { return $this->linesSpreadsheet; }
    public function setLinesSpreadsheet(?string $v): self { $this->linesSpreadsheet = $v; return $this; }


}


