<?php

namespace App\Controller\Admin;

use App\Entity\VehicleAssignment;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class AssignmentDocumentController extends AbstractController
{
    #[Route('/admin/assignment/{assignmentId}/documents/new', name: 'admin_document_new_for_assignment')]
    public function new(
        int $assignmentId,
        Request $request,
        EntityManagerInterface $em,
        AdminUrlGenerator $adminUrlGenerator,
    ): RedirectResponse {
        $assignment = $em->getRepository(VehicleAssignment::class)->find($assignmentId);
        if (!$assignment) {
            throw $this->createNotFoundException('Assignment not found');
        }

        $returnTo = $request->query->get('returnTo');
        if (!is_string($returnTo) || $returnTo === '') {
            $returnTo = $request->headers->get('referer') ?: '/admin';
        }

        $url = $adminUrlGenerator
            ->setController(\App\Controller\Admin\DocumentCrudController::class)
            ->setAction('new')
            ->set('assignmentId', (string)$assignment->getId())
            ->set('returnTo', $returnTo)
            ->generateUrl();

        return $this->redirect($url);
    }
}
