<?php

namespace App\EventListener;

use App\Entity\InventoryTransaction;
use App\Entity\ServiceEventItem;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
class ServiceEventItemStockListener
{
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        if (!$em instanceof EntityManagerInterface) {
            return;
        }

        $uow = $em->getUnitOfWork();
        $metaTxn = $em->getClassMetadata(InventoryTransaction::class);

        // INSERTS: нова позиція ТО => списання
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$entity instanceof ServiceEventItem) continue;

            $txn = $this->makeTxn(
                type: 'service_use',
                warehouse: $entity->getWarehouse(),
                item: $entity->getItem(),
                qtyChange: $this->neg((string)$entity->getQtyUsed()),
                refType: 'ServiceEvent',
                refId: $entity->getServiceEvent()?->getId(),
                note: 'Списання по ТО'
            );

            $em->persist($txn);
            $uow->computeChangeSet($metaTxn, $txn);
        }

        // UPDATES: delta (кількість/склад/товар)
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof ServiceEventItem) continue;

            $changeSet = $uow->getEntityChangeSet($entity);

            $qtyChanged = array_key_exists('qtyUsed', $changeSet);
            $warehouseChanged = array_key_exists('warehouse', $changeSet);
            $itemChanged = array_key_exists('item', $changeSet);

            // змінився склад/товар => повернення старого + списання нового
            if ($warehouseChanged || $itemChanged) {
                $oldWarehouse = $warehouseChanged ? $changeSet['warehouse'][0] : $entity->getWarehouse();
                $oldItem      = $itemChanged ? $changeSet['item'][0] : $entity->getItem();
                $oldQty       = $qtyChanged ? (string)$changeSet['qtyUsed'][0] : (string)$entity->getQtyUsed();

                if ($oldWarehouse && $oldItem) {
                    $txnBack = $this->makeTxn(
                        type: 'service_use_delta',
                        warehouse: $oldWarehouse,
                        item: $oldItem,
                        qtyChange: (string)$oldQty, // +oldQty
                        refType: 'ServiceEvent',
                        refId: $entity->getServiceEvent()?->getId(),
                        note: 'Корекція ТО — повернення (зміна складу/товару)'
                    );

                    $em->persist($txnBack);
                    $uow->computeChangeSet($metaTxn, $txnBack);
                }

                $txnNew = $this->makeTxn(
                    type: 'service_use_delta',
                    warehouse: $entity->getWarehouse(),
                    item: $entity->getItem(),
                    qtyChange: $this->neg((string)$entity->getQtyUsed()), // -newQty
                    refType: 'ServiceEvent',
                    refId: $entity->getServiceEvent()?->getId(),
                    note: 'Корекція ТО — списання (зміна складу/товару)'
                );

                $em->persist($txnNew);
                $uow->computeChangeSet($metaTxn, $txnNew);

                continue;
            }

            // змінилась тільки кількість => delta
            if ($qtyChanged) {
                $old = (float)$changeSet['qtyUsed'][0];
                $new = (float)$changeSet['qtyUsed'][1];
                $delta = $new - $old;
                if (abs($delta) < 0.0005) continue;

                $qtyChange = $this->format3(-$delta); // qtyChange = -(new-old)

                $txn = $this->makeTxn(
                    type: 'service_use_delta',
                    warehouse: $entity->getWarehouse(),
                    item: $entity->getItem(),
                    qtyChange: $qtyChange,
                    refType: 'ServiceEvent',
                    refId: $entity->getServiceEvent()?->getId(),
                    note: 'Корекція ТО (delta кількості)'
                );

                $em->persist($txn);
                $uow->computeChangeSet($metaTxn, $txn);
            }
        }

        // DELETES: видалили позицію ТО => повернення
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if (!$entity instanceof ServiceEventItem) continue;

            $txn = $this->makeTxn(
                type: 'service_use_delta',
                warehouse: $entity->getWarehouse(),
                item: $entity->getItem(),
                qtyChange: (string)$entity->getQtyUsed(), // +qtyUsed
                refType: 'ServiceEvent',
                refId: $entity->getServiceEvent()?->getId(),
                note: 'Видалення позиції ТО — повернення'
            );

            $em->persist($txn);
            $uow->computeChangeSet($metaTxn, $txn);
        }
    }

    private function makeTxn(
        string $type,
        object $warehouse,
        object $item,
        string $qtyChange,
        string $refType,
        ?int $refId,
        string $note
    ): InventoryTransaction {
        $txn = new InventoryTransaction();
        $txn->setType($type);
        $txn->setWarehouse($warehouse);
        $txn->setItem($item);
        $txn->setQtyChange($qtyChange);
        $txn->setRefType($refType);
        $txn->setRefId($refId);
        $txn->setNote($note);
        return $txn;
    }

    private function neg(string $v): string
    {
        $v = trim($v);
        return str_starts_with($v, '-') ? substr($v, 1) : '-' . $v;
    }

    private function format3(float $v): string
    {
        return rtrim(rtrim(number_format($v, 3, '.', ''), '0'), '.') ?: '0';
    }
}
