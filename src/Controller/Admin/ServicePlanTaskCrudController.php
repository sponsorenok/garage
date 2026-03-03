<?php
// src/Controller/Admin/ServicePlanTaskCrudController.php

namespace App\Controller\Admin;

use App\Entity\ServicePlanTask;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class ServicePlanTaskCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ServicePlanTask::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Робота ТО')
            ->setEntityLabelInPlural('Роботи ТО');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Назва роботи')
                ->setRequired(true)
                ->setHelp('Напр. "Заміна масла", "Масляний фільтр"'),

            IntegerField::new('intervalKm', 'Інтервал, км')
                ->setHelp('Після виконання (напр. 5000)')
                ->setFormTypeOption('empty_data', null),

            IntegerField::new('intervalDays', 'Інтервал, днів')
                ->setHelp('365 = 1 рік')
                ->setFormTypeOption('empty_data', null),

            IntegerField::new('soonKm', 'Скоро, км')
                ->setHelp('Якщо порожньо — береться з плану')
                ->setFormTypeOption('empty_data', null),

            IntegerField::new('soonDays', 'Скоро, днів')
                ->setHelp('Якщо порожньо — береться з плану')
                ->setFormTypeOption('empty_data', null),

        ];
    }
}
