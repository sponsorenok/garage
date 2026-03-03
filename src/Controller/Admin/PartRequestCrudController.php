<?php

namespace App\Controller\Admin;

use App\Admin\Filter\VehicleAnyFilter;
use App\Entity\PartRequest;
use App\Entity\Vehicle;
use App\Enum\PartRequestCategory;
use App\Form\Type\PartRequestItemsSpreadsheetType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\Form\FormInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
final class PartRequestCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $em,
        private AdminUrlGenerator $adminUrlGenerator
) {}

    public static function getEntityFqcn(): string
    {
        return PartRequest::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Заявка')
            ->setEntityLabelInPlural('Заявки')
            ->setDefaultSort(['createdAt' => 'DESC'])
            // базовий пошук (розширений для авто/позицій робимо в createIndexQueryBuilder)
            ->setSearchFields(['note', 'status', 'department.name'])
            ->setFormThemes([
                'admin/form/items_spreadsheet_widget.html.twig',
                '@EasyAdmin/crud/form_theme.html.twig',
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        $createPurchase = Action::new('createPurchaseFromRequests', 'Створити закупівлю', 'fa fa-cart-plus')
            ->linkToCrudAction('createPurchaseFromRequests')
            ->addCssClass('btn btn-primary');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $createPurchase->createAsBatchAction());

    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('department', 'Підрозділ'))
            ->add(ChoiceFilter::new('status', 'Статус')->setChoices([
                'Чернетка' => 'DRAFT',
                'Відправлено' => 'SUBMITTED',
                'В роботі' => 'IN_PROGRESS',
                'Закрито' => 'DONE',
                'Скасовано' => 'CANCELED',
            ]))
            ->add(\App\Admin\Filter\VehicleAnyFilter::new('vehicleAny', 'Авто'));
    }

    public function configureFields(string $pageName): iterable
    {
        $statusChoices = [
            'Чернетка' => 'DRAFT',
            'Відправлено' => 'SUBMITTED',
            'В роботі' => 'IN_PROGRESS',
            'Закрито' => 'DONE',
            'Скасовано' => 'CANCELED',
        ];

        if ($pageName === Crud::PAGE_INDEX) {
            yield DateTimeField::new('createdAt', 'Дата');
            yield AssociationField::new('department', 'Підрозділ');

            yield ChoiceField::new('status', 'Статус')
                ->setChoices($statusChoices)
                ->renderAsBadges([
                    'DRAFT' => 'secondary',
                    'SUBMITTED' => 'info',
                    'IN_PROGRESS' => 'warning',
                    'DONE' => 'success',
                    'CANCELED' => 'danger',
                ])
                ->onlyOnIndex();

            yield TextField::new('vehiclesSummary', 'Авто')->onlyOnIndex();
            return;
        }

        if ($pageName === Crud::PAGE_DETAIL) {
            yield DateTimeField::new('createdAt', 'Дата');
            yield AssociationField::new('department', 'Підрозділ');

            yield ChoiceField::new('status', 'Статус')
                ->setChoices($statusChoices)
                ->renderAsBadges([
                    'DRAFT' => 'secondary',
                    'SUBMITTED' => 'info',
                    'IN_PROGRESS' => 'warning',
                    'DONE' => 'success',
                    'CANCELED' => 'danger',
                ])
                ->onlyOnDetail();

            yield AssociationField::new('defaultVehicle', 'Авто за замовчуванням')
                ->setRequired(false)
                ->onlyOnDetail();

            yield TextareaField::new('note', 'Примітка')->onlyOnDetail();

            yield TextField::new('itemsTable', 'Запчастини')
                ->setTemplatePath('admin/part_request/items_table.html.twig')
                ->onlyOnDetail();

            return;
        }

        // NEW / EDIT
        yield AssociationField::new('department', 'Підрозділ');

        // ✅ тепер дату можна редагувати
        yield DateTimeField::new('createdAt', 'Дата')
            ->setFormTypeOption('widget', 'single_text');

        yield AssociationField::new('defaultVehicle', 'Авто за замовчуванням')
            ->setRequired(false);

        yield ChoiceField::new('status', 'Статус')->setChoices($statusChoices);

        yield TextareaField::new('note', 'Примітка')->setRequired(false);

        $vehiclesJson = json_encode($this->getVehiclesForDropdown(), JSON_UNESCAPED_UNICODE);
        $typesJson = json_encode($this->getTypesForDropdown(), JSON_UNESCAPED_UNICODE);

        yield TextareaField::new('itemsSpreadsheet', 'Запчастини (таблиця)')
            ->setFormType(PartRequestItemsSpreadsheetType::class)
            ->setFormTypeOption('mapped', false)
            ->setFormTypeOptions([
                'vehicles_json' => $vehiclesJson,
                'types_json' => $typesJson,
            ])
            ->onlyOnForms();
    }

    public function createPurchaseFromRequests(AdminContext $context): Response
    {
        $req = $context->getRequest();

        // EA batch ids можуть приходити по-різному
        $ids = $req->request->all('entityIds');
        if (!$ids) {
            $ids = $req->query->all('entityIds');
        }
        $ids = array_values(array_filter(array_map('intval', (array)$ids)));

        if (!$ids) {
            $this->addFlash('warning', 'Не вибрано жодної заявки.');
            return $this->redirect($this->adminUrlGenerator
                ->setController(self::class)
                ->setAction(Crud::PAGE_INDEX)
                ->generateUrl()
            );
        }

        $form = $this->createForm(CreatePurchaseFromRequestsType::class, [
            'purchaseDate' => new \DateTime(),
            'currency' => 'UAH',
        ]);

        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $purchase = new Purchase();
            $purchase->setWarehouse($data['warehouse']);
            $purchase->setSupplier($data['supplier'] ?? null);
            $purchase->setPurchaseDate($data['purchaseDate']);
            $purchase->setCurrency($data['currency']);
            $purchase->setInvoiceNumber($data['invoiceNumber'] ?? null);
            $purchase->setNotes($data['notes'] ?? null);

            // ✅ прив’язуємо заявки як контекст
            $requests = $this->em->getRepository(PartRequest::class)->findBy(['id' => $ids]);
            foreach ($requests as $pr) {
                $purchase->addRequest($pr);
            }

            $this->em->persist($purchase);
            $this->em->flush();

            $this->addFlash('success', 'Закупівлю створено. Додай позиції та документи.');

            // редірект на EDIT закупівлі
            $url = $this->adminUrlGenerator
                ->setController(PurchaseCrudController::class)
                ->setAction(Crud::PAGE_EDIT)
                ->setEntityId($purchase->getId())
                ->generateUrl();

            return $this->redirect($url);
        }

        return $this->render('admin/part_request/create_purchase_from_requests.html.twig', [
            'form' => $form->createView(),
            'ids' => $ids,
        ]);
    }


    /**
     * ✅ Розширюємо пошук на index:
     * - PartRequestItem.nameRaw
     * - Vehicle make/model/plate для items.vehicle
     * - Vehicle make/model/plate для defaultVehicle
     */
    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $root = $qb->getRootAliases()[0] ?? 'entity';

        // joins для пошуку по автівках/позиціях
        $qb->leftJoin($root . '.items', 'pri_items')
            ->leftJoin('pri_items.vehicle', 'pri_vehicle')
            ->leftJoin($root . '.defaultVehicle', 'pri_def_vehicle')
            ->addSelect('pri_items', 'pri_vehicle', 'pri_def_vehicle')
            ->distinct();

        $q = trim((string) $searchDto->getQuery());
        if ($q !== '') {
            $param = '%' . mb_strtolower($q) . '%';

            $qb->andWhere(
                'LOWER(pri_items.nameRaw) LIKE :q OR
                 LOWER(pri_vehicle.make) LIKE :q OR LOWER(pri_vehicle.model) LIKE :q OR LOWER(pri_vehicle.plate) LIKE :q OR
                 LOWER(pri_def_vehicle.make) LIKE :q OR LOWER(pri_def_vehicle.model) LIKE :q OR LOWER(pri_def_vehicle.plate) LIKE :q'
            )->setParameter('q', $param);
        }

        return $qb;
    }

    private function getVehiclesForDropdown(): array
    {
        $vehicles = $this->em->getRepository(Vehicle::class)
            ->createQueryBuilder('v')
            ->orderBy('v.make', 'ASC')
            ->addOrderBy('v.model', 'ASC')
            ->addOrderBy('v.plate', 'ASC')
            ->addOrderBy('v.id', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(static function (Vehicle $v) {
            return ['id' => $v->getId(), 'label' => (string) $v];
        }, $vehicles);
    }

    private function getTypesForDropdown(): array
    {
        $out = [];
        foreach (PartRequestCategory::choices() as $label => $value) {
            $out[] = ['label' => $label, 'value' => $value];
        }
        return $out;
    }

    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        /** @var PartRequest $req */
        $req = $entityInstance;
        $this->applySpreadsheetItems($req);
        parent::persistEntity($em, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        /** @var PartRequest $req */
        $req = $entityInstance;
        $this->applySpreadsheetItems($req);
        parent::updateEntity($em, $entityInstance);
    }

    private function applySpreadsheetItems(PartRequest $req): void
    {
        $request = $this->getContext()->getRequest();
        if (!$request) return;

        $all = $request->request->all();
        $payload = $all['PartRequest'] ?? $all['part_request'] ?? null;

        if (!is_array($payload)) {
            foreach ($all as $v) {
                if (is_array($v) && array_key_exists('itemsSpreadsheet', $v)) {
                    $payload = $v;
                    break;
                }
            }
        }

        if (!is_array($payload) || !array_key_exists('itemsSpreadsheet', $payload)) {
            return;
        }

        $json = (string)($payload['itemsSpreadsheet'] ?? '');
        if (trim($json) === '') return;

        $rows = json_decode($json, true);
        if (!is_array($rows)) return;


// ✅ страховка: якщо rows порожній масив і в БД вже є items — не стирати
        if (count($rows) === 0 && $req->getItems()->count() > 0) {
            return;
        }

        foreach ($req->getItems()->toArray() as $old) {
            $req->removeItem($old);
        }

        $line = 1;
        foreach ($rows as $row) {
            $name = trim((string)($row['nameRaw'] ?? ''));
            if ($name === '') continue;

            $item = new \App\Entity\PartRequestItem();
            $item->setLineNo($line++);
            $item->setNameRaw($name);
            $item->setCategory(($row['category'] ?? null) ?: null);

            $qtyRaw = $row['qty'] ?? '1.000';

// приймаємо 1,5 або "1.5" або 1
            $qtyStr = is_string($qtyRaw) ? $qtyRaw : (string)$qtyRaw;
            $item->setQty($qtyStr);

            $vehicleId = isset($row['vehicleId']) ? (int)$row['vehicleId'] : null;
            if ($vehicleId) {
                $item->setVehicle($this->em->getReference(Vehicle::class, $vehicleId));
            } else {
                $item->setVehicle($req->getDefaultVehicle());
            }

            $req->addItem($item);
        }
    }

    public function createNewForm(
        EntityDto $entityDto,
        KeyValueStore $formOptions,
        AdminContext $context
    ): FormInterface {
        $form = parent::createNewForm($entityDto, $formOptions, $context);

        if ($form->has('itemsSpreadsheet')) {
            $form->get('itemsSpreadsheet')->setData('[]');
        }

        return $form;
    }

    public  function createEditForm(
        EntityDto $entityDto,
        KeyValueStore $formOptions,
        AdminContext $context
    ): FormInterface {
        $form = parent::createEditForm($entityDto, $formOptions, $context);

        /** @var PartRequest $req */
        $req = $entityDto->getInstance();

        if ($form->has('itemsSpreadsheet')) {
            $form->get('itemsSpreadsheet')->setData($this->itemsToJson($req));
        }

        return $form;
    }

    private function itemsToJson(PartRequest $req): string
    {
        $rows = [];
        foreach ($req->getItems() as $it) {
            $rows[] = [
                // id можеш лишати або ні — зараз він не використовується, але не заважає
                'id'        => $it->getId(),
                'nameRaw'   => $it->getNameRaw(),
                'category'  => $it->getCategory(),          // VALUE (ENGINE, ...)
                'qty'       => $it->getQty(),
                'vehicleId' => $it->getVehicle()?->getId(),
            ];
        }

        return json_encode($rows, JSON_UNESCAPED_UNICODE);
    }

}
