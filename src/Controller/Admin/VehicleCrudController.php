<?php

namespace App\Controller\Admin;

use App\Entity\Vehicle;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
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
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;

class VehicleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Vehicle::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('plate', 'Військовий номер'),
            TextField::new('vin', 'VIN')->hideOnIndex(),

            TextField::new('make', 'Марка базового шасі '),
            TextField::new('model', 'Модель'),
            IntegerField::new('year', 'Рік')->hideOnIndex(),

            IntegerField::new('currentOdometerRm', 'Пробіг, км'),
            NumberField::new('currentEngineHours', 'Мотогодини')->hideOnIndex(),

            AssociationField::new('type', 'Найменування зразка ОіВТ')
                ->setRequired(false),

            AssociationField::new('department', 'Підрозділ')
                ->setRequired(false),

            AssociationField::new('staffSlot', 'Штатна позиція')
                ->setRequired(false)
                ->setHelp('Призначає автівку на штатний слот підрозділу'),

            TextareaField::new('notes', 'Нотатки')->hideOnIndex(),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('department', 'Підрозділ'))
            ->add(EntityFilter::new('type', 'Тип автівки'))
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
            ->setSearchFields(['plate', 'vin', 'make', 'model']);
    }

    private RequestStack $requestStack;
    private EntityManagerInterface $em;

    public function __construct(
        RequestStack $requestStack,
        private DocumentRepository $documentRepo,
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
                $responseParameters->set('docs', $docs);
            }
        }

        return $responseParameters;
    }

}
