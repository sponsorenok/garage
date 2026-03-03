<?php

namespace App\Application\Purchase;

use App\Domain\Number\Decimal;
use App\Entity\Item;
use App\Entity\PartRequestItem;
use App\Entity\Purchase;
use App\Entity\PurchaseLine;
use App\Entity\PurchaseLineRequestItemAllocation;
use Doctrine\ORM\EntityManagerInterface;

final class PurchaseLinesSpreadsheetApplier
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * @param string $jsonRows JSON array: [{requestItemId, itemId, buyQty}, ...]
     */
    public function apply(Purchase $purchase, string $jsonRows): void
    {
        $rows = json_decode($jsonRows ?: '[]', true);
        if (!is_array($rows)) $rows = [];

        // existing REQUEST lines indexed by requestItemId
        $existingByReqItemId = [];
        foreach ($purchase->getLines() as $line) {
            if ($line instanceof PurchaseLine
                && $line->getSourceType() === 'REQUEST'
                && $line->getRequestItemId()
            ) {
                $existingByReqItemId[(int)$line->getRequestItemId()] = $line;
            }
        }

        $seenReqItemIds = [];

        foreach ($rows as $r) {
            if (!is_array($r)) continue;

            $requestItemId = (int)($r['requestItemId'] ?? 0);
            $itemId        = (int)($r['itemId'] ?? 0);
            $buyQty        = Decimal::normalize($r['buyQty'] ?? '0', 3);

            if ($requestItemId <= 0) continue;
            if ($itemId <= 0) continue;
            if (!Decimal::gtZero($buyQty)) continue;

            $seenReqItemIds[$requestItemId] = true;

            $line = $existingByReqItemId[$requestItemId] ?? new PurchaseLine();

            if (!$line->getId()) {
                $line->setPurchase($purchase);
                $purchase->addLine($line);
            }

            $line->setSourceType('REQUEST');
            $line->setRequestItemId($requestItemId);

            $line->setItem($this->em->getReference(Item::class, $itemId));
            $line->setQty($buyQty);

            // one allocation per requestItemId
            $alloc = null;
            foreach ($line->getAllocations() as $a) {
                if ($a instanceof PurchaseLineRequestItemAllocation
                    && $a->getRequestItem()?->getId() === $requestItemId
                ) {
                    $alloc = $a;
                    break;
                }
            }

            if (!$alloc) {
                $alloc = new PurchaseLineRequestItemAllocation();
                $alloc->setRequestItem($this->em->getReference(PartRequestItem::class, $requestItemId));
                $line->addAllocation($alloc);
            }

            $alloc->setQty($buyQty);
        }

        // remove REQUEST lines that are no longer present
        foreach ($purchase->getLines()->toArray() as $line) {
            if (!$line instanceof PurchaseLine) continue;
            if ($line->getSourceType() !== 'REQUEST') continue;

            $rid = $line->getRequestItemId();
            if ($rid && !isset($seenReqItemIds[$rid])) {
                $purchase->removeLine($line);
            }
        }
    }
}
