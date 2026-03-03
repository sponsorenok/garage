<?php

namespace App\Controller\Admin;

use App\Entity\Department;
use App\Entity\Vehicle;
use App\Service\VehicleAssignmentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

final class VehicleDepartmentAssignController extends AbstractController
{
    #[Route('/admin/vehicle/{vehicleId}/assign-department/{departmentId}', name: 'admin_vehicle_assign_department')]
    public function assign(
        int $vehicleId,
        int $departmentId,
        EntityManagerInterface $em,
        VehicleAssignmentManager $assignmentManager,
        Request $request
    ): RedirectResponse {
        $vehicle = $em->getRepository(Vehicle::class)->find($vehicleId);
        $department = $em->getRepository(Department::class)->find($departmentId);

        if (!$vehicle || !$department) {
            $this->addFlash('danger', 'Автівку або підрозділ не знайдено.');
            return $this->redirect('/admin');
        }

        // ставимо в підрозділ, слот очищаємо
        $vehicle->setStaffSlot(null);
        $vehicle->setDepartment($department);

        // створюємо подію (slot = null)
        $assignment = $assignmentManager->startNew($vehicle, $department, null, 'Призначено в підрозділ без слота');

        $em->flush();

        $this->addFlash('success', 'Автівку призначено в підрозділ. Додайте документ.');

        $returnTo = $request->query->get('returnTo');
        if (!is_string($returnTo) || $returnTo === '') $returnTo = '/admin';

        return $this->redirectToRoute('admin_document_new_for_assignment', [
            'assignmentId' => $assignment->getId(),
            'returnTo' => $returnTo,
        ]);
    }
}

