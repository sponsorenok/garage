<?php

namespace App\Controller\Admin;

use App\Entity\Item;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Item::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Позиція')
            ->setEntityLabelInPlural('Номенклатура')
            ->setPageTitle(Crud::PAGE_INDEX, 'Номенклатура')
            ->setPageTitle(Crud::PAGE_NEW, 'Додати позицію')
            ->setPageTitle(Crud::PAGE_EDIT, 'Редагувати позицію');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('sku', 'Артикул (SKU)')
                ->setHelp('Необовʼязково. Якщо заповниш — бажано щоб був унікальний.')
                ->hideOnIndex(),

            TextField::new('name', 'Назва'),

            ChoiceField::new('unit', 'Одиниця виміру')
                ->setChoices([
                    'шт' => 'pcs',
                    'л'  => 'liter',
                    'кг' => 'kg',
                ])
                ->renderExpanded(false) // dropdown
                ->allowMultipleChoices(false)
                ->setHelp('В БД зберігається: pcs / liter / kg'),

            BooleanField::new('trackLot', 'Облік партій (рідини)')
                ->setHelp('Увімкни, якщо потрібно вести партії/термін придатності (наприклад масло).'),

            BooleanField::new('trackSerial', 'Серійні номери')
                ->setHelp('Увімкни, якщо кожен екземпляр має серійний номер.'),

            TextareaField::new('notes', 'Нотатки')->hideOnIndex(),
        ];
    }
}
