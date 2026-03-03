<?php

namespace App\Service;

use App\Entity\Department;
use App\Entity\DepartmentVehicleSlot;
use App\Entity\Vehicle;
use App\Entity\VehicleAssignment;
use App\Repository\VehicleAssignmentRepository;
use Doctrine\ORM\EntityManagerInterface;

final class VehicleAssignmentManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private VehicleAssignmentRepository $repo,
    ) {}

    public function startNew(Vehicle $vehicle, Department $department, ?DepartmentVehicleSlot $slot, ?string $note = null): VehicleAssignment
    {
        $active = $this->repo->findActiveByVehicle($vehicle);
        if ($active) {
            $active->setIsActive(false);
        }

        $a = new VehicleAssignment();
        $a->setVehicle($vehicle);
        $a->setDepartment($department);
        $a->setStaffSlot($slot);
        $a->setIsActive(true);
        $a->setNote($note);

        $this->em->persist($a);
        return $a;
    }
}
