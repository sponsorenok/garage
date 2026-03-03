<?php

namespace App\Controller\Admin;

use App\Entity\Warehouse;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class WarehouseCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Warehouse::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Склад')
            ->setEntityLabelInPlural('Склади')
            ->setPageTitle(Crud::PAGE_INDEX, 'Склади')
            ->setPageTitle(Crud::PAGE_NEW, 'Додати склад')
            ->setPageTitle(Crud::PAGE_EDIT, 'Редагувати склад');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Назва'),
            TextField::new('address', 'Адреса')->hideOnIndex(),
            TextareaField::new('notes', 'Нотатки')->hideOnIndex(),
        ];
    }
}
