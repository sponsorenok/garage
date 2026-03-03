<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'part_request_item')]
class PartRequestItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PartRequest $request = null;

    #[ORM\Column]
    private int $lineNo = 1;

    #[ORM\Column(length: 255)]
    private string $nameRaw = '';

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $category = null; // Choice value

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Vehicle $vehicle = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    private string $qty = '1.000';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3, options: ['default' => '0.000'])]
    private string $receivedQty = '0.000';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    public function getId(): ?int { return $this->id; }

    public function getRequest(): ?PartRequest { return $this->request; }
    public function setRequest(?PartRequest $request): self { $this->request = $request; return $this; }

    public function getLineNo(): int { return $this->lineNo; }
    public function setLineNo(int $no): self { $this->lineNo = max(1, $no); return $this; }

    public function getNameRaw(): string { return $this->nameRaw; }
    public function setNameRaw(string $name): self { $this->nameRaw = trim($name); return $this; }

    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $category): self { $this->category = $category; return $this; }

    public function getVehicle(): ?Vehicle { return $this->vehicle; }
    public function setVehicle(?Vehicle $vehicle): self { $this->vehicle = $vehicle; return $this; }

    public function getQty(): string { return $this->qty; }
    public function setQty(string $qty): self
    {
        $v = (float) str_replace(',', '.', $qty);
        if ($v <= 0) $v = 1.0;
        $this->qty = number_format($v, 3, '.', '');
        return $this;
    }

    public function getReceivedQty(): string { return $this->receivedQty; }
    public function setReceivedQty(string $qty): self
    {
        $v = (float) str_replace(',', '.', $qty);
        if ($v < 0) $v = 0.0;
        $this->receivedQty = number_format($v, 3, '.', '');
        return $this;
    }

    public function getOpenQty(): string
    {
        $open = (float)$this->qty - (float)$this->receivedQty;
        if ($open < 0) $open = 0;
        return number_format($open, 3, '.', '');
    }

    public function getComment(): ?string { return $this->comment; }
    public function setComment(?string $comment): self { $this->comment = $comment; return $this; }

    public function __toString(): string
    {
        return sprintf('%d) %s', $this->lineNo, $this->nameRaw ?: '—');
    }

    public function getCategoryLabel(): string
    {
        return \App\Enum\PartRequestCategory::label($this->category);
    }
}
