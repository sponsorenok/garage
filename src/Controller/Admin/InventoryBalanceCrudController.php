<?php

namespace App\Controller\Admin;

use App\Dto\InventoryBalance;
use App\Controller\Admin\InventoryTransactionCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

final class InventoryBalanceCrudController extends \EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController
{
    public function __construct(private AdminUrlGenerator $adminUrlGenerator)
    {
    }

    public static function getEntityFqcn(): string
    {
        // якщо ти підставляєш DTO як “entity” для CRUD
        return InventoryBalance::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $ledger = Action::new('ledger', 'Рух складу')
            ->linkToUrl(function (InventoryBalance $row) {
                return $this->adminUrlGenerator
                    ->setController(InventoryTransactionCrudController::class)
                    ->setAction(Crud::PAGE_INDEX)
                    ->set('filters[warehouse][value]', (string)$row->warehouseId)
                    ->set('filters[item][value]', (string)$row->itemId)
                    ->set('sort', 'createdAt')
                    ->set('direction', 'DESC')
                    ->generateUrl();
            });

        return $actions->add(Crud::PAGE_INDEX, $ledger);
    }
}
