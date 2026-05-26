<?php

namespace App\Entity;

use App\Repository\VehicleImageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: VehicleImageRepository::class)]
#[ORM\Index(columns: ['vehicle_id'], name: 'idx_vehicle_image_vehicle')]
#[ORM\Index(columns: ['document_id'], name: 'idx_vehicle_image_document')]
#[ORM\Index(columns: ['event_date'], name: 'idx_vehicle_image_event_date')]
#[Vich\Uploadable]
class VehicleImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Vehicle $vehicle = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Document $document = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $eventDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $odometerKm = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[Vich\UploadableField(mapping: 'vehicle_images', fileNameProperty: 'fileName', size: 'fileSize')]
    #[Assert\Image(
        maxSize: '10M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
        mimeTypesMessage: 'Дозволено лише JPG/PNG/WebP'
    )]
    private ?File $imageFile = null;

    /**
     * @var UploadedFile[]
     */
    #[Assert\All([
        new Assert\Image(
            maxSize: '10M',
            mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
            mimeTypesMessage: 'Дозволено лише JPG/PNG/WebP'
        ),
    ])]
    private array $imageFiles = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileName = null;

    #[ORM\Column(nullable: true)]
    private ?int $fileSize = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getVehicle(): ?Vehicle { return $this->vehicle; }
    public function setVehicle(?Vehicle $vehicle): self { $this->vehicle = $vehicle; return $this; }

    public function getDocument(): ?Document { return $this->document; }
    public function setDocument(?Document $document): self { $this->document = $document; return $this; }

    public function getEventDate(): ?\DateTimeInterface { return $this->eventDate; }
    public function setEventDate(?\DateTimeInterface $eventDate): self { $this->eventDate = $eventDate; return $this; }

    public function getOdometerKm(): ?int { return $this->odometerKm; }
    public function setOdometerKm(?int $odometerKm): self { $this->odometerKm = $odometerKm; return $this; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): self { $this->title = $title; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): self { $this->notes = $notes; return $this; }

    public function getImageFile(): ?File { return $this->imageFile; }
    public function setImageFile(?File $imageFile): void
    {
        $this->imageFile = $imageFile;
        if ($imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    /**
     * @return UploadedFile[]
     */
    public function getImageFiles(): array
    {
        return $this->imageFiles;
    }

    /**
     * @param UploadedFile[]|null $imageFiles
     */
    public function setImageFiles(?array $imageFiles): void
    {
        $this->imageFiles = array_values(array_filter($imageFiles ?? []));
        if ($this->imageFiles) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getFileName(): ?string { return $this->fileName; }
    public function setFileName(?string $fileName): self { $this->fileName = $fileName; return $this; }

    public function getFileSize(): ?int { return $this->fileSize; }
    public function setFileSize(?int $fileSize): self { $this->fileSize = $fileSize; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }

    public function __toString(): string
    {
        return $this->title ?: ($this->fileName ?: 'Фото авто');
    }
}
