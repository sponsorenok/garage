<?php

namespace App\Controller\Admin;

use App\Entity\ServiceEventItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
class ServiceEventItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ServiceEventItem::class;
    }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Позиція ТО')
            ->setEntityLabelInPlural('Позиції ТО');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('warehouse', 'Склад'),
            AssociationField::new('item', 'Номенклатура'),
            NumberField::new('qtyUsed', 'Кількість'),
            NumberField::new('unitCostSnapshot', 'Ціна (знімок)')->hideOnIndex(),
            TextareaField::new('notes', 'Нотатки')->hideOnIndex(),
        ];
    }
}
