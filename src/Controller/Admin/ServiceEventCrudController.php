<?php

namespace App\Controller\Admin;

use App\Entity\ServiceEvent;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use App\Entity\Vehicle;
use App\Entity\ServicePlan;
use App\Entity\ServiceEventTask;
class ServiceEventCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ServiceEvent::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Запис ТО')
            ->setEntityLabelInPlural('ТО та ремонти')
            ->setPageTitle(Crud::PAGE_INDEX, 'ТО та ремонти')
            ->setPageTitle(Crud::PAGE_NEW, 'Додати запис ТО')
            ->setPageTitle(Crud::PAGE_EDIT, 'Редагувати запис ТО');
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('vehicle', 'Автівка');
        yield DateField::new('serviceDate', 'Дата');

        yield IntegerField::new('odometerKm', 'Пробіг, км')->hideOnIndex();
        yield NumberField::new('engineHours', 'Мотогодини')->hideOnIndex();

        yield TextField::new('title', 'Назва/робота');
        yield TextareaField::new('notes', 'Нотатки')->hideOnIndex();

        yield NumberField::new('laborCost', 'Вартість робіт')->hideOnIndex();
        yield NumberField::new('otherCost', 'Інші витрати')->hideOnIndex();

        yield CollectionField::new('tasks', 'Роботи')
            ->useEntryCrudForm(ServiceEventTaskCrudController::class)
            ->setFormTypeOptions([
                'by_reference' => false, // ✅ критично, як і в items
            ])
            ->renderExpanded()
            ->allowAdd(false)
            ->allowDelete(false)
            ->onlyOnForms(); // щоб не роздувати index


        // ⬇️ Оце і є “декілька запчастин та рідин” в одному ТО
        yield CollectionField::new('items', 'Позиції (запчастини/рідини)')
            ->useEntryCrudForm(ServiceEventItemCrudController::class)
            ->setFormTypeOptions([
                'by_reference' => false, // критично! щоб працював addItem/removeItem
            ])
            ->onlyOnForms();

        yield TextField::new('tasksProgressText', 'Прогрес')
            ->onlyOnIndex()
            ->formatValue(function ($value, ServiceEvent $event) {
                $p = $event->getTasksProgress();
                if ($p['total'] === 0) return '—';
                if ($p['isAllDone']) return '✅ '.$p['text'];
                if ($p['isStarted']) return '🟡 '.$p['text'];
                return '⚪ '.$p['text'];
            });
    }

    public function createEntity(string $entityFqcn)
    {
        $event = new ServiceEvent();

        $req = $this->getContext()?->getRequest();
        $vehicleId = $req?->query->getInt('vehicleId') ?: null;
        $planId    = $req?->query->getInt('planId') ?: null;

        $em = $this->container->get('doctrine')->getManager();

        // 1) vehicle
        if ($vehicleId) {
            $vehicle = $em->getRepository(Vehicle::class)->find($vehicleId);
            if ($vehicle) {
                $event->setVehicle($vehicle);
                $event->setOdometerKm($vehicle->getOdometerKm());
            }
        }

        // 2) plan
        $plan = null;
        if ($planId) {
            $plan = $em->getRepository(ServicePlan::class)->find($planId);
            if ($plan) {
                $event->setServicePlan($plan);
            }
        }

        // 3) generate tasks from plan (✅ тепер $plan вже не null)
        if ($plan) {
            foreach ($plan->getTasks() as $pt) {
                $t = new ServiceEventTask();
                $t->setPlanTask($pt);
                $t->setName($pt->getName());
                $t->setIntervalKm($pt->getIntervalKm());
                $t->setIntervalDays($pt->getIntervalDays());

                // якщо в task пороги не задані — беремо з плану
                $t->setSoonKm($pt->getSoonKm() ?? $plan->getSoonKm());
                $t->setSoonDays($pt->getSoonDays() ?? $plan->getSoonDays());

                $t->setStatus(ServiceEventTask::STATUS_PLANNED);

                $event->addTask($t);
            }

            // ✅ fallback: якщо в плані 0 задач — створюємо одну “загальну”
            if ($plan->getTasks()->isEmpty()) {
                $t = new ServiceEventTask();
                $t->setName($plan->getName().' (загальна робота)');
                $t->setStatus(ServiceEventTask::STATUS_PLANNED);
                $event->addTask($t);
            }
        }

        return $event;
    }


}
