<?php

namespace App\Controller\Admin;

use App\Entity\PurchaseLineRequestItemAllocation;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

final class PurchaseAllocationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PurchaseLineRequestItemAllocation::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('requestItem', 'Позиція заявки')
            ->setRequired(true);

        yield NumberField::new('qty', 'К-сть закрити')
            ->setRequired(true);
    }
}
