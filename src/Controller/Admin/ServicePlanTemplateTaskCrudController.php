<?php

namespace App\Controller\Admin;

use App\Entity\ServicePlanTemplateTask;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ServicePlanTemplateTaskCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ServicePlanTemplateTask::class;
    }


    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Назва задачі');

yield IntegerField::new('intervalKm', 'Інтервал км')->hideOnIndex();
yield IntegerField::new('intervalDays', 'Інтервал дні')->hideOnIndex();

yield IntegerField::new('soonKm', 'Скоро км')->hideOnIndex();
yield IntegerField::new('soonDays', 'Скоро дні')->hideOnIndex();
    }

}
