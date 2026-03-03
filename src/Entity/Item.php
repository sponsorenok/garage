<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80, nullable: true,unique: true)]
    private ?string $sku = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 16)]
    private ?string $unit = null;

    #[ORM\Column]
    private ?bool $trackLot =false;

    #[ORM\Column]
    private ?bool $trackSerial = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    public function isTrackLot(): ?bool
    {
        return $this->trackLot;
    }

    public function setTrackLot(bool $trackLot): static
    {
        $this->trackLot = $trackLot;

        return $this;
    }

    public function isTrackSerial(): ?bool
    {
        return $this->trackSerial;
    }

    public function setTrackSerial(bool $trackSerial): static
    {
        $this->trackSerial = $trackSerial;

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
        return $this->getName() ?? 'Позиція';
    }

}
