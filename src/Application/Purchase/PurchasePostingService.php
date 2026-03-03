<?php

namespace App\Application\Purchase;

use App\Domain\Number\Decimal;
use App\Entity\InventoryTransaction;
use App\Entity\Purchase;
use App\Entity\PurchaseLine;
use App\Entity\PurchaseLineRequestItemAllocation;
use Doctrine\ORM\EntityManagerInterface;

final class PurchasePostingService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function post(Purchase $purchase): void
    {
        if (($purchase->getStatus() ?? 'DRAFT') === 'POSTED') {
            return; // idempotent
        }

        // idempotency guard: already created inventory transactions
        $existingCount = (int) $this->em->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(InventoryTransaction::class, 't')
            ->andWhere('t.refType = :rt')
            ->andWhere('t.refId = :rid')
            ->setParameter('rt', 'PURCHASE')
            ->setParameter('rid', (string)$purchase->getId())
            ->getQuery()
            ->getSingleScalarResult();

        if ($existingCount > 0) {
            $purchase->setStatus('POSTED');
            return;
        }

        // 1) create InventoryTransaction per line
        foreach ($purchase->getLines() as $line) {
            if (!$line instanceof PurchaseLine) continue;

            $qty = Decimal::normalize($line->getQty() ?? '0', 3);
            if (!Decimal::gtZero($qty)) continue;

            $tx = new InventoryTransaction();
            $tx->settype('PURCHASE_IN');

            $wh = $line->getWarehouse() ?: $purchase->getWarehouse();
            $tx->setWarehouse($wh);

            $tx->setItem($line->getItem());
            $tx->setQtyChange($qty);
            $tx->setUnitCost($line->getUnitCost());

            $tx->setRefType('PURCHASE');
            $tx->setRefId((string)$purchase->getId());

            $note = trim((string)$purchase->getInvoiceNumber());
            $tx->setNote($note ? ('Закупівля №'.$note) : ('Закупівля #'.$purchase->getId()));

            $this->em->persist($tx);
        }

        // 2) apply allocations => receivedQty
        foreach ($purchase->getLines() as $line) {
            if (!$line instanceof PurchaseLine) continue;

            foreach ($line->getAllocations() as $alloc) {
                if (!$alloc instanceof PurchaseLineRequestItemAllocation) continue;

                $ri = $alloc->getRequestItem();
                if (!$ri) continue;

                $add = Decimal::normalize($alloc->getQty() ?? '0', 3);
                if (!Decimal::gtZero($add)) continue;

                $current = Decimal::normalize($ri->getReceivedQty() ?? '0', 3);
                $new = Decimal::add($current, $add, 3);

                // clamp to qty
                $max = Decimal::normalize($ri->getQty() ?? '0', 3);
                // compare: bcmath compare if possible
                if (function_exists('bccomp')) {
                    if (bccomp($new, $max, 3) === 1) $new = $max;
                } else {
                    if ((float)$new > (float)$max) $new = $max;
                }

                $ri->setReceivedQty($new);
            }
        }

        $purchase->setStatus('POSTED');
    }
}
