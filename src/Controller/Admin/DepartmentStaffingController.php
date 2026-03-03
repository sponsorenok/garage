<?php

namespace App\Controller\Admin;

use App\Entity\Department;
use App\Repository\VehicleAssignmentRepository;
use App\Repository\VehicleRepository;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DepartmentStaffingController extends AbstractController
{
    #[Route('/admin/departments/{id}/staffing', name: 'admin_department_staffing')]
    public function staffing(
        Department $department,
        VehicleRepository $vehicleRepo,
        VehicleAssignmentRepository $assignmentRepo,
        AdminUrlGenerator $adminUrlGenerator,
    ): Response {
        $slots = $department->getStaffSlots();

        $slotIds = [];
        foreach ($slots as $s) {
            if ($s->getId()) $slotIds[] = $s->getId();
        }

        $assignedMap = $vehicleRepo->findByStaffSlotIdsMap($slotIds);

        $rows = [];
        $vehicleIdsInSlots = [];

        foreach ($slots as $slot) {
            $vehicle = $slot->getId() ? ($assignedMap[$slot->getId()] ?? null) : null;
            if ($vehicle && $vehicle->getId()) {
                $vehicleIdsInSlots[] = $vehicle->getId();
            }

            // mismatch: type
            $typeMismatch = false;
            if ($vehicle && $vehicle->getType() && $slot->getType()) {
                $typeMismatch = $vehicle->getType()->getId() !== $slot->getType()->getId();
            }

            // mismatch: brand (опційно)
            $slotBrand = $slot->getBrand();
            $vehicleBrand = null;
            if ($vehicle) {
                if (method_exists($vehicle, 'getBrand')) {
                    $vehicleBrand = $vehicle->getBrand();
                } elseif (method_exists($vehicle, 'getMake')) {
                    $vehicleBrand = $vehicle->getMake();
                }
            }

            $brandMismatch = false;
            if ($vehicle && $slotBrand) {
                if ($vehicleBrand === null) {
                    $brandMismatch = false;
                } else {
                    $brandMismatch = mb_strtolower(trim((string)$vehicleBrand)) !== mb_strtolower(trim((string)$slotBrand));
                }
            }

            $rows[] = [
                'slot' => $slot,
                'vehicle' => $vehicle,
                'missing' => $vehicle === null,
                'typeMismatch' => $typeMismatch,
                'brandMismatch' => $brandMismatch,
                'slotBrand' => $slotBrand,
                'vehicleBrand' => $vehicleBrand,
                // 'assignment' додамо нижче
            ];
        }

        // ✅ активні події для авто, які стоять у слотах
        $activeAssignmentsMap = $assignmentRepo->findActiveMapByVehicles($vehicleIdsInSlots);

        foreach ($rows as &$r) {
            $vid = $r['vehicle']?->getId();
            $r['assignment'] = $vid ? ($activeAssignmentsMap[$vid] ?? null) : null;
        }
        unset($r);

        // ✅ підсумки
        $summary = [
            'total' => count($rows),
            'missing' => count(array_filter($rows, fn($r) => $r['missing'])),
            'mismatch' => count(array_filter($rows, fn($r) => $r['typeMismatch'] || $r['brandMismatch'])),
            'ok' => count(array_filter($rows, fn($r) => !$r['missing'] && !$r['typeMismatch'] && !$r['brandMismatch'])),
        ];

        // ✅ авто у підрозділі без слота
        $freeVehicles = $vehicleRepo->findInDepartmentWithoutSlot($department->getId());
        $freeIds = [];
        foreach ($freeVehicles as $v) {
            if ($v->getId()) $freeIds[] = $v->getId();
        }
        $freeAssignmentsMap = $assignmentRepo->findActiveMapByVehicles($freeIds);

        return $this->render('admin/department_staffing.html.twig', [
            'department' => $department,
            'rows' => $rows,
            'summary' => $summary,
            'freeVehicles' => $freeVehicles,
            'freeAssignments' => $freeAssignmentsMap,
        ]);
    }
}
