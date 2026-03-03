<?php

namespace App\Controller\Admin;

use App\Entity\PartRequestItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PurchaseAjaxController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/admin/ajax/purchase/request-items', name: 'admin_ajax_purchase_request_items', methods: ['GET'])]
    public function requestItems(Request $request): JsonResponse
    {
        $ids = $request->query->all('ids') ?? [];
        $ids = array_values(array_filter(array_map('intval', (array)$ids)));

        if (!$ids) {
            return new JsonResponse(['rows' => []]);
        }

        $qb = $this->em->getRepository(PartRequestItem::class)->createQueryBuilder('i')
            ->join('i.request', 'r')
            ->leftJoin('i.vehicle', 'v')
            ->andWhere('r.id IN (:ids)')->setParameter('ids', $ids)
            ->orderBy('r.id', 'ASC')
            ->addOrderBy('i.lineNo', 'ASC');

        /** @var PartRequestItem[] $items */
        $items = $qb->getQuery()->getResult();

        $rows = [];
        foreach ($items as $it) {
            $qty = (string) $it->getQty();
            $received = (string) ($it->getReceivedQty() ?? '0.000');

            $openQty = (float) str_replace(',', '.', $qty) - (float) str_replace(',', '.', $received);
            if ($openQty <= 0) continue;

            $rows[] = [
                'requestId'     => $it->getRequest()?->getId(),
                'requestItemId' => $it->getId(),
                'nameRaw'       => $it->getNameRaw(),
                'category'      => $it->getCategory(), // VALUE
                'openQty'       => number_format($openQty, 3, '.', ''),
                'vehicleLabel'  => $it->getVehicle() ? (string) $it->getVehicle() : '',
            ];
        }

        return new JsonResponse(['rows' => $rows]);
    }
}
