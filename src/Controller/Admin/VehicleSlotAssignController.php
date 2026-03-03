<?php

namespace App\Controller\Admin;

use App\Entity\Vehicle;
use App\Entity\DepartmentVehicleSlot;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\VehicleAssignmentManager;
use App\Entity\VehicleAssignment;
use App\Repository\VehicleAssignmentRepository;

final class VehicleSlotAssignController extends AbstractController
{
    #[Route('/admin/vehicle/{vehicleId}/assign-slot/{slotId}', name: 'admin_vehicle_assign_slot')]
    public function assign(
        int $vehicleId,
        int $slotId,
        EntityManagerInterface $em,
        VehicleAssignmentManager $assignmentManager,
        Request $request
    ): RedirectResponse {
        $vehicle = $em->getRepository(Vehicle::class)->find($vehicleId);
        $slot    = $em->getRepository(DepartmentVehicleSlot::class)->find($slotId);

        if (!$vehicle || !$slot) {
            $this->addFlash('danger', 'Автівку або слот не знайдено.');
            return $this->redirectToRoute('admin'); // або інша твоя домашня адмін-сторінка
        }

        // ✅ якщо авто вже має слот — не призначаємо
        if ($vehicle->getStaffSlot()) {
            $this->addFlash('warning', 'Автівка вже призначена на штатну позицію.');
            return $this->redirect($this->getReturnUrl());
        }

        // ✅ перевірка типу (опційно, але корисно)
        if ($vehicle->getType() && $slot->getType()) {
            if ($vehicle->getType()->getId() !== $slot->getType()->getId()) {
                $this->addFlash('danger', 'Тип автівки не відповідає типу штатної позиції.');
                return $this->redirect($this->getReturnUrl());
            }
        }

        // ✅ перевірка що слот вільний (важливо!)
        $occupied = $em->getRepository(Vehicle::class)->findOneBy(['staffSlot' => $slot]);
        if ($occupied) {
            $this->addFlash('danger', 'Ця штатна позиція вже зайнята іншою автівкою.');
            return $this->redirect($this->getReturnUrl());
        }



        // ✅ автопідстановка підрозділу
        if ($slot->getDepartment()) {
            $vehicle->setDepartment($slot->getDepartment());
        }

        $vehicle->setStaffSlot($slot);
        if ($slot->getDepartment()) {
            $vehicle->setDepartment($slot->getDepartment());
        }
        // ✅ створюємо подію
        $assignment = $assignmentManager->startNew(
            $vehicle,
            $slot->getDepartment(),
            $slot,
            'Призначено на штатну позицію'
        );
        $em->flush();

        $this->addFlash('success', 'Автівку призначено на штатну позицію. Додайте документ.');

        return $this->redirectToRoute('admin_document_new_for_assignment', [
            'assignmentId' => $assignment->getId(),
            'returnTo' => $this->getReturnUrl(),
        ]);
    }

    private function getReturnUrl(): string
    {
        // повертаємося туди, звідки прийшли
        $req = $this->getCurrentRequest();
        $returnTo = $req?->query->get('returnTo');

        if (is_string($returnTo) && $returnTo !== '') {
            return $returnTo;
        }

        $referer = $req?->headers->get('referer');
        return $referer ?: '/admin';
    }

    private function getCurrentRequest()
    {
        return $this->container->get('request_stack')->getCurrentRequest();
    }


}
