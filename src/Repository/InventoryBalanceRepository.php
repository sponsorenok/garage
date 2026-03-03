<?php

namespace App\Repository;

use App\Dto\InventoryBalance;
use Doctrine\DBAL\Connection;

final class InventoryBalanceRepository
{
    public function __construct(private readonly Connection $db) {}

    /** @return InventoryBalance[] */
    public function fetchAll(bool $onlyMoved = true): array
    {
        // onlyMoved=true => показуємо тільки ті item+warehouse, де були рухи
        // (це найпрактичніше, і не роздуває список)
        $sql = <<<SQL
            SELECT
                w.id   AS warehouse_id,
                w.name AS warehouse_name,
                i.id   AS item_id,
                i.name AS item_name,
                i.unit AS unit,
                SUM(t.qty_change) AS qty_balance
            FROM inventory_transaction t
            JOIN warehouse w ON w.id = t.warehouse_id
            JOIN item i ON i.id = t.item_id
            GROUP BY w.id, w.name, i.id, i.name, i.unit
            ORDER BY w.name, i.name
        SQL;

        if (!$onlyMoved) {
            // Варіант “показати все”: склад×товар навіть без рухів.
            // Увага: може бути дуже багато рядків.
            $sql = <<<SQL
                SELECT
                    w.id   AS warehouse_id,
                    w.name AS warehouse_name,
                    i.id   AS item_id,
                    i.name AS item_name,
                    i.unit AS unit,
                    COALESCE(SUM(t.qty_change), 0) AS qty_balance
                FROM warehouse w
                CROSS JOIN item i
                LEFT JOIN inventory_transaction t
                    ON t.warehouse_id = w.id AND t.item_id = i.id
                GROUP BY w.id, w.name, i.id, i.name, i.unit
                ORDER BY w.name, i.name
            SQL;
        }

        $rows = $this->db->fetchAllAssociative($sql);

        return array_map(
            fn(array $r) => new InventoryBalance(
                (int)$r['warehouse_id'],
                (string)$r['warehouse_name'],
                (int)$r['item_id'],
                (string)$r['item_name'],
                (string)$r['unit'],
                // PG може повертати numeric як string — це ок
                (string)$r['qty_balance']
            ),
            $rows
        );
    }
}
