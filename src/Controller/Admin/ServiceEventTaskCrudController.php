<?php
// src/Controller/Admin/ServiceEventTaskCrudController.php

namespace App\Controller\Admin;

use App\Entity\ServiceEventTask;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;

final class ServiceEventTaskCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ServiceEventTask::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Робота')
            ->setEntityLabelInPlural('Роботи')
            ->setDefaultSort(['id' => 'ASC']);
    }

    /**
     * ВАЖЛИВО: вбудовані (inline) записи не мають мати “перехід на окремі сторінки”.
     * Тому ховаємо NEW/DETAIL/INDEX і лишаємо редагування тільки в колекції батька.
     */
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::INDEX, Action::NEW, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Робота')->setRequired(true),

            ChoiceField::new('status', 'Статус')->setChoices([
                'Заплановано' => ServiceEventTask::STATUS_PLANNED,
                'Виконано'    => ServiceEventTask::STATUS_DONE,
                'Пропущено'   => ServiceEventTask::STATUS_SKIPPED,
            ])->renderAsBadges([
                ServiceEventTask::STATUS_PLANNED => 'secondary',
                ServiceEventTask::STATUS_DONE    => 'success',
                ServiceEventTask::STATUS_SKIPPED => 'warning',
            ]),

            IntegerField::new('doneOdometerKm', 'Пробіг виконання')
                ->setHelp('Якщо пусто — можна автоматично підставляти з пробігу ТО'),

            DateField::new('doneDate', 'Дата виконання')
                ->setHelp('Якщо пусто — можна автоматично підставляти з дати ТО'),
        ];
    }
}
