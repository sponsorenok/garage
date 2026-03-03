<?php

namespace App\Controller\Admin;

use App\Entity\ServiceEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ServiceEventCloseController extends AbstractController
{
    #[Route('/admin/service-event/{id}/close', name: 'admin_service_event_close', methods: ['POST'])]
    public function close(ServiceEvent $event, EntityManagerInterface $em, Request $request): RedirectResponse
    {
        $p = $event->getTasksProgress(true);
        if ($p['total'] > 0 && !$p['isAllDone']) {
            $this->addFlash('danger', 'Не всі задачі виконані — неможливо закрити ТО.');
            return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('admin'));
        }

        $event->close();
        $em->flush();

        $this->addFlash('success', 'ТО закрито.');

        // ✅ Повертаємось на сторінку нагадувань з EA-контекстом (menuIndex тощо)
        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('admin'));
    }
}
