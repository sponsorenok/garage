<?php

namespace App\Controller\Admin;

use App\Entity\VehicleType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class VehicleTypeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return VehicleType::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Найменування зразка ОіВТ')
            ->setEntityLabelInPlural('Найменування зразків ОіВТ')
            ->setDefaultSort(['name' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield TextField::new('name', 'Найменування зразка ОіВТ');
    }
}
