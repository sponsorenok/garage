<?php

namespace App\Controller\Admin;

use App\Entity\PurchaseLine;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
class PurchaseLineCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PurchaseLine::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Позиція закупівлі')
            ->setEntityLabelInPlural('Позиції закупівлі');
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('item', 'Номенклатура')->setRequired(true);

        yield AssociationField::new('warehouse', 'Склад (необовʼязково)')
            ->setHelp('Якщо не вибрати — буде використано склад із “шапки” закупівлі.')
            ->setRequired(false);

        yield NumberField::new('qty', 'Кількість')->setRequired(true);
        yield NumberField::new('unitCost', 'Ціна за одиницю')->setRequired(false);

        yield TextField::new('lotCode', 'Партія')->hideOnIndex();
        yield DateField::new('expiryDate', 'Термін придатності')->hideOnIndex();
        yield CollectionField::new('allocations', 'Закриття заявок (allocations)')
            ->useEntryCrudForm(PurchaseAllocationCrudController::class)
            ->setFormTypeOptions(['by_reference' => false])
            ->onlyOnForms();

        yield TextareaField::new('notes', 'Нотатки')->hideOnIndex();
    }
}
