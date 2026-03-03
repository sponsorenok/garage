<?php

namespace App\Controller\Admin;

use App\Entity\DepartmentVehicleSlot;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class DepartmentVehicleSlotCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DepartmentVehicleSlot::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Штатна позиція')
            ->setEntityLabelInPlural('Штатні позиції');
    }

    public function configureFields(string $pageName): iterable
    {
        // В inline-режимі ці поля теж працюють
        yield IdField::new('id')->onlyOnIndex();

        // Department тут можна НЕ показувати, бо ми редагуємо слоти всередині Department
        // Але якщо відкриєш CRUD окремо — корисно мати:
        yield AssociationField::new('department', 'Підрозділ')->hideOnForm();

        yield AssociationField::new('type', 'Тип')->setRequired(true);

        yield TextField::new('brand', 'Марка (текст)')
            ->setRequired(false);

        yield TextField::new('title', 'Назва/роль (опц.)')
            ->setRequired(false)
            ->setHelp('Напр: "Вантажна #2", "Командирська"');
    }
}
