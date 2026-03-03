<?php

namespace App\Controller\Admin;

use App\Entity\ServicePlanTemplate;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ServicePlanTemplateCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ServicePlanTemplate::class;
    }


    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Назва');
        yield IntegerField::new('intervalKm', 'Інтервал, км')->hideOnIndex();
        yield IntegerField::new('intervalDays', 'Інтервал, дні')->hideOnIndex();

        yield IntegerField::new('absoluteDueOdometerKm', 'Абсолютний пробіг')->hideOnIndex();
        yield DateField::new('absoluteDueAt', 'Абсолютна дата')->hideOnIndex();

        yield IntegerField::new('soonKm', 'Скоро, км')->hideOnIndex();
        yield IntegerField::new('soonDays', 'Скоро, дні')->hideOnIndex();

        yield BooleanField::new('isActive', 'Активний')->hideOnIndex();

        yield CollectionField::new('tasks', 'Задачі шаблону')
            ->useEntryCrudForm(ServicePlanTemplateTaskCrudController::class)
            ->setFormTypeOptions(['by_reference' => false])
            ->renderExpanded()
            ->allowAdd(true)
            ->allowDelete(true)
            ->onlyOnForms();

    }

}
