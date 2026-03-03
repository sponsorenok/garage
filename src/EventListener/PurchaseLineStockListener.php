<?php

namespace App\EventListener;

use App\Entity\InventoryTransaction;
use App\Entity\PurchaseLine;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
class PurchaseLineStockListener
{
    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        if (!$em instanceof EntityManagerInterface) return;

        $uow = $em->getUnitOfWork();
        $metaTxn = $em->getClassMetadata(InventoryTransaction::class);

        // INSERTS: нова позиція закупівлі => прихід (+qty)
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$entity instanceof PurchaseLine) continue;

            $warehouse = $this->resolveWarehouse($entity);
            if (!$warehouse) continue;

            $txn = $this->makeTxn(
                type: 'purchase_in',
                warehouse: $warehouse,
                item: $entity->getItem(),
                qtyChange: (string)$entity->getQty(),
                unitCost: $entity->getUnitCost(),
                refType: 'Purchase',
                refId: $entity->getPurchase()?->getId(),
                note: 'Прихід по закупівлі'
            );

            $em->persist($txn);
            $uow->computeChangeSet($metaTxn, $txn);
        }

        // UPDATES: delta (qty/warehouse/item)
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof PurchaseLine) continue;

            $cs = $uow->getEntityChangeSet($entity);

            $qtyChanged = array_key_exists('qty', $cs);
            $whChanged  = array_key_exists('warehouse', $cs) || array_key_exists('purchase', $cs); // purchase може змінити "шапковий" склад
            $itemChanged= array_key_exists('item', $cs);

            // якщо змінився item/warehouse — робимо "зняти старе" + "додати нове"
            if ($itemChanged || $whChanged) {
                $oldItem = $itemChanged ? $cs['item'][0] : $entity->getItem();

                // старий склад: або старий warehouse, або старий purchase.warehouse
                $oldWarehouse = null;
                if (array_key_exists('warehouse', $cs)) {
                    $oldWarehouse = $cs['warehouse'][0] ?: null;
                }
                if (!$oldWarehouse && array_key_exists('purchase', $cs) && $cs['purchase'][0]) {
                    $oldWarehouse = $cs['purchase'][0]->getWarehouse();
                }
                if (!$oldWarehouse) {
                    // fallback: якщо нічого не знайшли, беремо поточний (щоб не “зламати” flush)
                    $oldWarehouse = $this->resolveWarehouse($entity);
                }

                $oldQty = $qtyChanged ? (string)$cs['qty'][0] : (string)$entity->getQty();

                // 1) прибрати старий прихід: -oldQty
                if ($oldWarehouse && $oldItem) {
                    $txnOut = $this->makeTxn(
                        type: 'purchase_delta',
                        warehouse: $oldWarehouse,
                        item: $oldItem,
                        qtyChange: $this->neg($oldQty),
                        unitCost: $entity->getUnitCost(),
                        refType: 'Purchase',
                        refId: $entity->getPurchase()?->getId(),
                        note: 'Корекція закупівлі — прибрати старе (зміна складу/товару)'
                    );
                    $em->persist($txnOut);
                    $uow->computeChangeSet($metaTxn, $txnOut);
                }

                // 2) додати новий прихід: +newQty
                $newWarehouse = $this->resolveWarehouse($entity);
                if ($newWarehouse) {
                    $txnIn = $this->makeTxn(
                        type: 'purchase_delta',
                        warehouse: $newWarehouse,
                        item: $entity->getItem(),
                        qtyChange: (string)$entity->getQty(),
                        unitCost: $entity->getUnitCost(),
                        refType: 'Purchase',
                        refId: $entity->getPurchase()?->getId(),
                        note: 'Корекція закупівлі — додати нове (зміна складу/товару)'
                    );
                    $em->persist($txnIn);
                    $uow->computeChangeSet($metaTxn, $txnIn);
                }

                continue;
            }

            // тільки qty: додаємо різницю (new-old)
            if ($qtyChanged) {
                $old = (float)$cs['qty'][0];
                $new = (float)$cs['qty'][1];
                $delta = $new - $old;
                if (abs($delta) < 0.0005) continue;

                $warehouse = $this->resolveWarehouse($entity);
                if (!$warehouse) continue;

                $txn = $this->makeTxn(
                    type: 'purchase_delta',
                    warehouse: $warehouse,
                    item: $entity->getItem(),
                    qtyChange: $this->format3($delta), // +delta
                    unitCost: $entity->getUnitCost(),
                    refType: 'Purchase',
                    refId: $entity->getPurchase()?->getId(),
                    note: 'Корекція закупівлі (delta кількості)'
                );

                $em->persist($txn);
                $uow->computeChangeSet($metaTxn, $txn);
            }
        }

        // DELETES: видалили позицію => прибрати прихід (-qty)
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if (!$entity instanceof PurchaseLine) continue;

            $warehouse = $this->resolveWarehouse($entity);
            if (!$warehouse) continue;

            $txn = $this->makeTxn(
                type: 'purchase_delta',
                warehouse: $warehouse,
                item: $entity->getItem(),
                qtyChange: $this->neg((string)$entity->getQty()),
                unitCost: $entity->getUnitCost(),
                refType: 'Purchase',
                refId: $entity->getPurchase()?->getId(),
                note: 'Видалення позиції закупівлі — прибрати прихід'
            );

            $em->persist($txn);
            $uow->computeChangeSet($metaTxn, $txn);
        }
    }

    private function resolveWarehouse(PurchaseLine $line): ?object
    {
        return $line->getWarehouse() ?: $line->getPurchase()?->getWarehouse();
    }

    private function makeTxn(
        string $type,
        object $warehouse,
        object $item,
        string $qtyChange,
               $unitCost,
        string $refType,
        ?int $refId,
        string $note
    ): InventoryTransaction {
        $txn = new InventoryTransaction();
        $txn->setType($type);
        $txn->setWarehouse($warehouse);
        $txn->setItem($item);
        $txn->setQtyChange($qtyChange);
        $txn->setUnitCost($unitCost); // якщо поле називається інакше — скажи, підлаштую
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
