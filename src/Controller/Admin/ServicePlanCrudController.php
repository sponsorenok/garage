<?php
// src/Controller/Admin/ServicePlanCrudController.php

namespace App\Controller\Admin;

use App\Entity\ServicePlan;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use App\Entity\ServicePlanTask;
use App\Entity\ServicePlanTemplate;
use Doctrine\ORM\EntityManagerInterface;
class ServicePlanCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ServicePlan::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('template', 'Шаблон')
                ->onlyOnForms()
                ->setRequired(false),
            AssociationField::new('vehicle', 'Авто'),
            TextField::new('name', 'План ТО'),

            IntegerField::new('intervalKm', 'Інтервал, км')->setHelp('Після останнього виконання (наприклад 5000)'),
            IntegerField::new('intervalDays', 'Інтервал, днів')->setHelp('Після останнього виконання (365 = 1 рік)'),

            IntegerField::new('absoluteDueOdometerKm', 'Абсолютно по пробігу')->setHelp('Напр. 100000 — велике ТО'),
            DateField::new('absoluteDueAt', 'Абсолютна дата')->setHelp('Якщо треба'),

            IntegerField::new('soonKm', 'Скоро, км')->setHelp('За скільки км починати попереджати'),
            IntegerField::new('soonDays', 'Скоро, днів')->setHelp('За скільки днів попереджати'),

            CollectionField::new('tasks', 'Шаблонні роботи')
                ->useEntryCrudForm(ServicePlanTaskCrudController::class)
                ->allowAdd()
                ->allowDelete()
                ->renderExpanded(),

            BooleanField::new('isActive', 'Активний'),
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof ServicePlan) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        $template = $entityInstance->getTemplate();

        // якщо обрали шаблон — копіюємо в план
        if ($template instanceof ServicePlanTemplate) {
            // ✅ назва плану = назва шаблону (як ти захотів)
            $entityInstance->setName($template->getName());

            // поля як у плану
            $entityInstance->setIntervalKm($template->getIntervalKm());
            $entityInstance->setIntervalDays($template->getIntervalDays());
            $entityInstance->setAbsoluteDueOdometerKm($template->getAbsoluteDueOdometerKm());
            $entityInstance->setAbsoluteDueAt($template->getAbsoluteDueAt());
            $entityInstance->setSoonKm($template->getSoonKm());
            $entityInstance->setSoonDays($template->getSoonDays());
            $entityInstance->setIsActive($template->isActive());

            // очищаємо задачі, якщо раптом щось додали вручну
            foreach ($entityInstance->getTasks() as $old) {
                $entityInstance->removeTask($old);
            }

            // копіюємо задачі
            foreach ($template->getTasks() as $tt) {
                $pt = new ServicePlanTask();
                $pt->setName($tt->getName());
                $pt->setIntervalKm($tt->getIntervalKm());
                $pt->setIntervalDays($tt->getIntervalDays());
                $pt->setSoonKm($tt->getSoonKm() ?? $template->getSoonKm());
                $pt->setSoonDays($tt->getSoonDays() ?? $template->getSoonDays());

                $entityInstance->addTask($pt);
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }
}
