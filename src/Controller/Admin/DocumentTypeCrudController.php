<?php

namespace App\Controller\Admin;

use App\Entity\DocumentType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class DocumentTypeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DocumentType::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Тип документа')
            ->setEntityLabelInPlural('Типи документів')
            ->setDefaultSort(['name' => 'ASC'])
            ->setSearchFields(['code', 'name']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('code', 'Code'))
            ->add(TextFilter::new('name', 'Name'))
            ->add(BooleanFilter::new('isActive', 'Активний'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('code', 'Code')
            ->setHelp('Напр: VEHICLE_ASSIGN, STOCK_RECEIPT, STOCK_TRANSFER');

        yield TextField::new('name', 'Назва');
        yield BooleanField::new('isActive', 'Активний');
    }
}
