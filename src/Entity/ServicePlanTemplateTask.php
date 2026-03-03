<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'service_plan_template_task')]
class ServicePlanTemplateTask
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ServicePlanTemplate $template = null;

    #[ORM\Column(length: 255)]
    private string $name;

    // як у ServicePlanTask (task-level лишаємо)
    #[ORM\Column(type:'integer', nullable: true)]
    private ?int $intervalKm = null;

    #[ORM\Column(type:'integer', nullable: true)]
    private ?int $intervalDays = null;

    #[ORM\Column(type:'integer', nullable: true)]
    private ?int $soonKm = null;

    #[ORM\Column(type:'integer', nullable: true)]
    private ?int $soonDays = null;

    public function getId(): ?int { return $this->id; }

    public function getTemplate(): ?ServicePlanTemplate { return $this->template; }
    public function setTemplate(?ServicePlanTemplate $t): self { $this->template = $t; return $this; }

    public function __toString(): string { return $this->name; }

    public function getName(): string { return $this->name; }
    public function setName(string $v): self { $this->name = $v; return $this; }

    public function getIntervalKm(): ?int { return $this->intervalKm; }
    public function setIntervalKm(?int $v): self { $this->intervalKm = $v; return $this; }

    public function getIntervalDays(): ?int { return $this->intervalDays; }
    public function setIntervalDays(?int $v): self { $this->intervalDays = $v; return $this; }

    public function getSoonKm(): ?int { return $this->soonKm; }
    public function setSoonKm(?int $v): self { $this->soonKm = $v; return $this; }

    public function getSoonDays(): ?int { return $this->soonDays; }
    public function setSoonDays(?int $v): self { $this->soonDays = $v; return $this; }
}
