<?php

namespace App\Dto;


final class InventoryBalance
{
    public function __construct(
        public readonly int $warehouseId,
        public readonly string $warehouseName,
        public readonly int $itemId,
        public readonly string $itemName,
        public readonly string $unit,          // pcs/liter/kg
        public readonly string $qtyBalance     // decimal як string
    ) {}
}
