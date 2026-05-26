<?php

namespace App\Controller\Admin;

use App\Entity\Vehicle;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\DepartmentVehicleSlot;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\DocumentRepository;
use App\Repository\VehicleImageRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;

class VehicleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Vehicle::class;
    }

    public function configureFields(string $pageName): iterable
    {
        if (Crud::PAGE_INDEX === $pageName) {
            return [
                TextField::new('plate', 'Військовий номер'),
                TextField::new('make', 'Марка базового шасі '),
                TextField::new('model', 'Модель'),
                AssociationField::new('type', 'Найменування зразка ОіВТ')
                    ->setRequired(false),
                AssociationField::new('department', 'Підрозділ')
                    ->setRequired(false),
                IntegerField::new('currentOdometerKm', 'Пробіг, км'),
            ];
        }

        return [
            FormField::addFieldset('Основні дані', 'fa fa-car'),
            TextField::new('make', 'Марка базового шасі ')
                ->setColumns(6),
            TextField::new('model', 'Модель')
                ->setColumns(6),
            AssociationField::new('type', 'Найменування ОіВТ')
                ->setRequired(false)
                ->setColumns(12),
            TextField::new('plate', 'Військовий номер')
                ->setColumns(6),
            TextField::new('vin', 'Номер шасі (VIN)')
                ->setColumns(6),
            TextField::new('engineNumber', 'Номер двигуна')
                ->setColumns(6),
            IntegerField::new('year', 'Рік виготовлення')
                ->setColumns(6),

            TextField::new('factoryNumber', 'Заводський номер ОіВТ')
                ->setColumns(6),
            ChoiceField::new('supportService', 'Служба забезпечення')
                ->setChoices([
                    'Служба 1' => 'service_1',
                    'Служба 2' => 'service_2',
                    'Служба 3' => 'service_3',
                    'Служба 4' => 'service_4',
                ])
                ->setColumns(6),
            ChoiceField::new('qualityCategory', 'Категорія (якісний стан)')
                ->setChoices([
                    'I' => 'cat_1',
                    'II' => 'cat_2',
                    'III' => 'cat_3',
                    'IV' => 'cat_4',
                    'V' => 'cat_5',
                ])
                ->setColumns(6),
            ChoiceField::new('operationGroup', 'Група експлуатації')
                ->setChoices([
                    'Група 1' => 'group_1',
                    'Група 2' => 'group_2',
                    'Група 3' => 'group_3',
                    'Група 4' => 'group_4',
                ])
                ->setColumns(6),
            NumberField::new('initialCost', 'Первісна вартість')
                ->setColumns(6),
            TextField::new('technicalCertificate', 'Технічний талон ТЗ')
                ->setColumns(6),
            TextField::new('commissioningOrder', 'Наказ на введення в експлуатацію')
                ->setColumns(6),

            FormField::addFieldset('Напрацювання', 'fa fa-gauge-high'),
            IntegerField::new('currentOdometerKm', 'Показник одометра, км')
                ->setColumns(6),
            IntegerField::new('annualMileageKm', 'Пробіг за рік, км')
                ->setColumns(6),
            NumberField::new('currentEngineHours', 'Мотогодини')
                ->setColumns(6),
            TextareaField::new('notes', 'Нотатки')
                ->setColumns(12),

            FormField::addFieldset('Надходження та передача', 'fa fa-exchange-alt'),
            DateField::new('receiptDate', 'Надходження')
                ->setColumns(6),
            TextField::new('receivedFrom', 'Надійшло від')
                ->setColumns(6),
            AssociationField::new('supplier', 'Постачальник')
                ->setRequired(false)
                ->setColumns(12),
            TextareaField::new('receivingDocuments', 'Документи на отримання')
                ->setColumns(12),

            TextField::new('sentTo', 'Відправлено до')
                ->setColumns(12),
            AssociationField::new('department', 'Передано в підрозділ')
                ->setRequired(false)
                ->setColumns(12),
            AssociationField::new('staffSlot', 'Штатна позиція')
                ->setRequired(false)
                ->setHelp('Призначає автівку на штатний слот підрозділу')
                ->setColumns(12),

            FormField::addFieldset('АКБ та шини', 'fa fa-car-battery'),
            IntegerField::new('batteryRequired', 'Потреба в АКБ')
                ->setColumns(6),
            IntegerField::new('batteryAvailable', 'Наявність АКБ')
                ->setColumns(6),
            IntegerField::new('tiresRequired', 'Потреба в шинах')
                ->setColumns(6),
            IntegerField::new('tiresAvailable', 'Наявність шин')
                ->setColumns(6),

            FormField::addFieldset('ТО, габарити та норми', 'fa fa-wrench'),
            IntegerField::new('maintenanceIntervalKm', 'Періодичність ТО, км')
                ->setColumns(6),
            DateField::new('lastMaintenanceDate', 'Дата останнього ТО')
                ->setColumns(6),
            TextField::new('registrationNumber', 'Реєстраційний номер ТЗ')
                ->setColumns(6),
            TextField::new('dimensions', 'Габаритні розміри (д*ш*в)')
                ->setColumns(6),
            IntegerField::new('grossWeightKg', 'Повна маса, кг')
                ->setColumns(6),
            IntegerField::new('curbWeightKg', 'Маса без навантаження, кг')
                ->setColumns(6),

            TextareaField::new('fuelConsumptionRates', 'Норми витрати пального')
                ->setColumns(12),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('department', 'Підрозділ'))
            ->add(EntityFilter::new('type', 'Тип автівки'))
            ->add(EntityFilter::new('supplier', 'Постачальник'))
            ->add(EntityFilter::new('staffSlot', 'Штатна позиція'));
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Автівка')
            ->setEntityLabelInPlural('Автівки')
            ->setPageTitle(Crud::PAGE_INDEX, 'Автівки')
            ->setPageTitle(Crud::PAGE_NEW, 'Додати автівку')
            ->setPageTitle(Crud::PAGE_EDIT, 'Редагувати автівку')
            ->showEntityActionsInlined()
            ->overrideTemplate('crud/detail', 'admin/vehicle/detail.html.twig')
            ->overrideTemplate('crud/new', 'admin/vehicle/new.html.twig')
            ->overrideTemplate('crud/edit', 'admin/vehicle/edit.html.twig')
            ->setSearchFields(['plate', 'vin', 'make', 'model', 'engineNumber', 'factoryNumber', 'registrationNumber']);
    }

    private RequestStack $requestStack;
    private EntityManagerInterface $em;

    public function __construct(
        RequestStack $requestStack,
        private DocumentRepository $documentRepo,
        private VehicleImageRepository $imageRepo,
        EntityManagerInterface $em
    ) {
        $this->requestStack = $requestStack;
        $this->em = $em;
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Vehicle) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $slotId = $request?->query->get('assignSlot');

        if ($slotId && !$entityInstance->getStaffSlot()) {
            $slot = $entityManager
                ->getRepository(DepartmentVehicleSlot::class)
                ->find($slotId);

            if ($slot) {
                $entityInstance->setStaffSlot($slot);

                // опційно: автоматично виставляємо підрозділ
                if (!$entityInstance->getDepartment()) {
                    $entityInstance->setDepartment($slot->getDepartment());
                }
            }
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) return $qb;

        // тільки авто без штатної позиції
        if ($request->query->getBoolean('unassigned')) {
            $qb->andWhere('entity.staffSlot IS NULL');
        }

        // тільки авто потрібного типу
        $typeId = (int)$request->query->get('typeId', 0);
        if ($typeId > 0) {
            $qb->andWhere('entity.type = :typeId')
                ->setParameter('typeId', $typeId);
        }

        return $qb;
    }

    public function configureActions(Actions $actions): Actions
    {
        $assign = Action::new('assign', 'Призначити')
            ->setIcon('fa fa-check')
            ->displayIf(function (Vehicle $v) {
                // показуємо кнопку тільки коли в URL є assignSlot і авто ще без слота
                $req = $this->requestStack->getCurrentRequest();
                return $req?->query->has('assignSlot') && $v->getStaffSlot() === null;
            })
            ->linkToUrl(function (Vehicle $v) {
                $req = $this->requestStack->getCurrentRequest();
                $slotId = (int)$req?->query->get('assignSlot', 0);

                // куди вертатися після призначення: або явно returnTo, або referer
                $returnTo = (string)$req?->query->get('returnTo', '');

                return $this->container->get(AdminUrlGenerator::class)
                    ->setRoute('admin_vehicle_assign_slot', [
                        'vehicleId' => $v->getId(),
                        'slotId'    => $slotId,
                        'returnTo'  => $returnTo,
                    ])
                    ->generateUrl();
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $assign)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
    {
        $responseParameters = parent::configureResponseParameters($responseParameters);

        // entity instance на detail сторінці
        $entityDto = $responseParameters->get('entity');
        if ($entityDto && method_exists($entityDto, 'getInstance')) {
            $vehicle = $entityDto->getInstance();

            if ($vehicle instanceof Vehicle && $vehicle->getId()) {
                $docs = $this->documentRepo->findForVehicle($vehicle->getId(), 50);
                $images = $this->imageRepo->findForVehicle($vehicle->getId(), 50);
                $responseParameters->set('docs', $docs);
                $responseParameters->set('images', $images);
            }
        }

        return $responseParameters;
    }

}
