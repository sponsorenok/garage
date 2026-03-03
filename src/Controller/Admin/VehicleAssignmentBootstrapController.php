<?php

namespace App\Controller\Admin;

use App\Entity\Vehicle;
use App\Repository\VehicleAssignmentRepository;
use App\Service\VehicleAssignmentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class VehicleAssignmentBootstrapController extends AbstractController
{
    #[Route('/admin/vehicle/{vehicleId}/bootstrap-assignment', name: 'admin_vehicle_assignment_bootstrap')]
    public function bootstrap(
        int $vehicleId,
        Request $request,
        EntityManagerInterface $em,
        VehicleAssignmentRepository $assignmentRepo,
        VehicleAssignmentManager $assignmentManager,
    ): RedirectResponse {
        $vehicle = $em->getRepository(Vehicle::class)->find($vehicleId);
        if (!$vehicle) {
            $this->addFlash('danger', 'Автівку не знайдено.');
            return $this->redirect($request->headers->get('referer') ?: '/admin');
        }

        $department = $vehicle->getDepartment();
        if (!$department) {
            $this->addFlash('danger', 'У автівки не задано підрозділ. Спочатку признач у підрозділ.');
            return $this->redirect($request->headers->get('referer') ?: '/admin');
        }

        // якщо активна подія вже є — просто йдемо додати документ
        $active = $assignmentRepo->findActiveByVehicle($vehicle);
        if (!$active) {
            $active = $assignmentManager->startNew(
                $vehicle,
                $department,
                $vehicle->getStaffSlot(),
                'Подію створено для існуючого стану (bootstrap)'
            );
            $em->flush();
        }

        $returnTo = $request->query->get('returnTo');
        if (!is_string($returnTo) || $returnTo === '') {
            $returnTo = $request->headers->get('referer') ?: '/admin';
        }

        return $this->redirectToRoute('admin_document_new_for_assignment', [
            'assignmentId' => $active->getId(),
            'returnTo' => $returnTo,
        ]);
    }
}
