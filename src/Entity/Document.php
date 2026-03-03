<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[ORM\Table(name: 'doc')]
#[ORM\Index(columns: ['created_at'], name: 'idx_doc_created_at')]
#[Vich\Uploadable]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?DocumentType $type = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?VehicleAssignment $assignment = null;


    // Дублюємо контекст (швидкі фільтри/пошук)
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Vehicle $vehicle = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Department $department = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $docNumber = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $docDate = null;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    // --- файл ---
    #[Vich\UploadableField(mapping: 'documents', fileNameProperty: 'fileName', size: 'fileSize')]
    #[Assert\File(
        maxSize: '25M',
        mimeTypes: [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
        mimeTypesMessage: 'Дозволено лише PDF/DOC/DOCX/XLS/XLSX'
    )]
    private ?File $file = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileName = null;

    #[ORM\Column(nullable: true)]
    private ?int $fileSize = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Purchase $purchase = null;

    public function getPurchase(): ?Purchase { return $this->purchase; }
    public function setPurchase(?Purchase $p): self { $this->purchase = $p; return $this; }

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getType(): ?DocumentType { return $this->type; }
    public function setType(?DocumentType $type): self { $this->type = $type; return $this; }

    public function getAssignment(): ?VehicleAssignment { return $this->assignment; }
    public function setAssignment(?VehicleAssignment $assignment): self { $this->assignment = $assignment; return $this; }

    public function getVehicle(): ?Vehicle { return $this->vehicle; }
    public function setVehicle(?Vehicle $vehicle): self { $this->vehicle = $vehicle; return $this; }

    public function getDepartment(): ?Department { return $this->department; }
    public function setDepartment(?Department $department): self { $this->department = $department; return $this; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): self { $this->title = $title; return $this; }

    public function getDocNumber(): ?string { return $this->docNumber; }
    public function setDocNumber(?string $docNumber): self { $this->docNumber = $docNumber; return $this; }

    public function getDocDate(): ?\DateTimeInterface { return $this->docDate; }
    public function setDocDate(?\DateTimeInterface $docDate): self { $this->docDate = $docDate; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $dt): self { $this->createdAt = $dt; return $this; }

    public function getFile(): ?File { return $this->file; }
    public function setFile(?File $file): void
    {
        $this->file = $file;
        if ($file) $this->updatedAt = new \DateTimeImmutable();
    }

    public function getFileName(): ?string { return $this->fileName; }
    public function setFileName(?string $name): self { $this->fileName = $name; return $this; }

    public function getFileSize(): ?int { return $this->fileSize; }
    public function setFileSize(?int $size): self { $this->fileSize = $size; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $dt): self { $this->updatedAt = $dt; return $this; }

    public function __toString(): string
    {
        return $this->title ?: ($this->fileName ?: 'Документ');
    }
}
