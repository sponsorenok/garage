<?php
// src/Entity/ServicePlanTask.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'service_plan_task')]
class ServicePlanTask
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ServicePlan $servicePlan = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type:'integer', nullable: true)]
    private ?int $intervalKm = null;

    #[ORM\Column(type:'integer', nullable: true)]
    private ?int $intervalDays = null;

    #[ORM\Column(type:'integer', nullable: true)]
    private ?int $soonKm = null;   // якщо null → беремо з plan.soonKm

    #[ORM\Column(type:'integer', nullable: true)]
    private ?int $soonDays = null; // якщо null → беремо з plan.soonDays


    // getters/setters (скорочено)
    public function getId(): ?int { return $this->id; }
    public function getServicePlan(): ?ServicePlan { return $this->servicePlan; }
    public function setServicePlan(?ServicePlan $p): self { $this->servicePlan = $p; return $this; }
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
