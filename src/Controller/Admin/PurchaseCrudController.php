<?php

namespace App\Controller\Admin;

use App\Entity\InventoryTransaction;
use App\Entity\Purchase;
use App\Entity\PurchaseLine;
use App\Entity\PurchaseLineRequestItemAllocation;
use App\Form\Type\PurchaseLinesSpreadsheetType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Entity\PartRequestItem;
use App\Entity\Item;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Enum\PartRequestCategory;
final class PurchaseCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $em,
        private AdminUrlGenerator $adminUrlGenerator,
        private \App\Application\Purchase\PurchaseLinesSpreadsheetApplier $linesApplier,
        private \App\Application\Purchase\PurchasePostingService $postingService,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Purchase::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Закупівля')
            ->setEntityLabelInPlural('Закупівлі')
            ->setPageTitle(Crud::PAGE_INDEX, 'Закупівлі')
            ->setPageTitle(Crud::PAGE_NEW, 'Додати закупівлю')
            ->setPageTitle(Crud::PAGE_EDIT, 'Редагувати закупівлю')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Перегляд закупівлі')
            ->setDefaultSort(['purchaseDate' => 'DESC'])->setFormThemes([
            'admin/form/purchase_lines_spreadsheet_widget.html.twig',
            '@EasyAdmin/crud/form_theme.html.twig',
        ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        $post = Action::new('postPurchase', 'Оприбуткувати', 'fa fa-check')
            ->linkToCrudAction('postPurchase')
            ->addCssClass('btn btn-success')
            ->displayIf(static function (Purchase $p) {
                return ($p->getStatus() ?? 'DRAFT') !== 'POSTED';
            });

        return $actions
            ->add(Crud::PAGE_DETAIL, $post)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            // опційно: показувати кнопку на index теж
            ->add(Crud::PAGE_INDEX, $post);
    }

    public function configureFields(string $pageName): iterable
    {
        $statusChoices = [
            'Чернетка' => 'DRAFT',
            'Оприбутковано' => 'POSTED',
            'Скасовано' => 'CANCELED',
        ];

        yield DateField::new('purchaseDate', 'Дата');
        yield AssociationField::new('warehouse', 'Склад')->setRequired(true);
        yield AssociationField::new('supplier', 'Постачальник')->setRequired(false);

        yield ChoiceField::new('status', 'Статус')
            ->setChoices($statusChoices)
            ->renderAsBadges([
                'DRAFT' => 'secondary',
                'POSTED' => 'success',
                'CANCELED' => 'danger',
            ])
            ->hideOnForm(); // статус змінюємо кнопкою “Оприбуткувати”

        yield TextField::new('invoiceNumber', 'Номер накладної')->hideOnIndex();
        yield TextField::new('currency', 'Валюта')->setHelp('Напр.: UAH / USD / EUR')->hideOnIndex();
        yield TextareaField::new('notes', 'Нотатки')->hideOnIndex();

        // ✅ вибір заявок
        yield AssociationField::new('requests', 'Заявки')
            ->setRequired(false)
            ->onlyOnForms();

        // ✅ Excel-like таблиця
        $itemsJson = json_encode($this->getItemsForDropdown(), JSON_UNESCAPED_UNICODE);
        $typesJson = json_encode($this->getTypesForDropdown(), JSON_UNESCAPED_UNICODE);

        $ajaxUrl = $this->generateUrl('admin_ajax_purchase_request_items', [], UrlGeneratorInterface::ABSOLUTE_PATH);


        yield TextareaField::new('linesSpreadsheet', 'Позиції (таблиця)')
            ->setFormType(PurchaseLinesSpreadsheetType::class)
            ->setFormTypeOption('mapped', false)
            ->setFormTypeOptions([
                'items_json' => $itemsJson,
                'types_json' => $typesJson,
                'ajax_url' => $ajaxUrl,
            ])
            ->onlyOnForms();

        // якщо DocumentCrudController існує - лишай
        yield CollectionField::new('documents', 'Документи')
            ->useEntryCrudForm(DocumentCrudController::class)
            ->setFormTypeOptions(['by_reference' => false])
            ->onlyOnForms();

        yield CollectionField::new('lines', 'Позиції закупівлі')
            ->useEntryCrudForm(PurchaseLineCrudController::class)
            ->setFormTypeOptions(['by_reference' => false])
            ->onlyOnForms();
    }

    /**
     * ✅ Оприбуткувати (створити InventoryTransaction + закрити заявки через allocations)
     */
    public function postPurchase(AdminContext $context): RedirectResponse
    {
        /** @var Purchase $purchase */
        $purchase = $context->getEntity()->getInstance();

        $this->postingService->post($purchase);
        $this->em->flush();

        $this->addFlash('success', 'Закупівлю оприбутковано.');
        return $this->redirectToPurchaseDetail($purchase);
    }

    private function redirectToPurchaseDetail(Purchase $purchase): RedirectResponse
    {
        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Crud::PAGE_DETAIL)
            ->setEntityId($purchase->getId())
            ->generateUrl();

        return $this->redirect($url);
    }

    #[Route('/admin/purchase/request-items', name: 'admin_purchase_request_items', methods: ['GET'])]
//    public function requestItemsAjax(): JsonResponse
//    {
//        $ids = $request->query->all('ids') ?? [];
////        $ids = array_values(array_filter(array_map('intval', (array)$ids)));
//
//        if (!$ids) {
//            return $this->json(['rows' => []]);
//        }
//
//        // беремо тільки відкриті позиції (qty > receivedQty)
//        $qb = $this->em->getRepository(PartRequestItem::class)->createQueryBuilder('i')
//            ->join('i.request', 'r')
//            ->leftJoin('i.vehicle', 'v')
//            ->andWhere('r.id IN (:ids)')->setParameter('ids', $ids)
//            ->orderBy('r.id', 'ASC')
//            ->addOrderBy('i.lineNo', 'ASC');
//
//        /** @var PartRequestItem[] $items */
//        $items = $qb->getQuery()->getResult();
//
//        $rows = [];
//        foreach ($items as $it) {
//            $qty = (string)$it->getQty();                // decimal-string
//            $received = (string)($it->getReceivedQty() ?? '0.000');
//            // openQty = qty - received (як decimal)
//            $openQty = (float)str_replace(',', '.', $qty) - (float)str_replace(',', '.', $received);
//            if ($openQty <= 0) continue;
//
//            $rows[] = [
//                'requestId'     => $it->getRequest()?->getId(),
//                'requestItemId' => $it->getId(),
//                'nameRaw'       => $it->getNameRaw(),
//                'category'      => $it->getCategory(), // VALUE
//                'openQty'       => number_format($openQty, 3, '.', ''),
//                'vehicleLabel'  => $it->getVehicle() ? (string)$it->getVehicle() : '',
//            ];
//        }
//
//        return $this->json(['rows' => $rows]);
//    }
    private function getItemsForDropdown(): array
    {
        $items = $this->em->getRepository(Item::class)
            ->createQueryBuilder('i')
            ->orderBy('i.name', 'ASC')
            ->addOrderBy('i.id', 'ASC')
            ->getQuery()->getResult();

        return array_map(static fn(Item $i) => [
            'id' => $i->getId(),
            'label' => (string)$i, // Item::__toString()
        ], $items);
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
        /** @var Purchase $purchase */
        $purchase = $entityInstance;

        $json = $this->extractLinesSpreadsheetJson();
        if ($json !== null) {
            $this->linesApplier->apply($purchase, $json);
        }

        parent::persistEntity($em, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        /** @var Purchase $purchase */
        $purchase = $entityInstance;

        $json = $this->extractLinesSpreadsheetJson();
        if ($json !== null) {
            $this->linesApplier->apply($purchase, $json);
        }

        parent::updateEntity($em, $entityInstance);
    }

    private function extractLinesSpreadsheetJson(): ?string
    {
        $request = $this->getContext()?->getRequest();
        if (!$request) return null;

        $payload = $request->request->all('Purchase');
        if (!is_array($payload) || !array_key_exists('linesSpreadsheet', $payload)) {
            return null;
        }

        return (string)($payload['linesSpreadsheet'] ?? '[]');
    }
    private function applyLinesSpreadsheet(Purchase $purchase): void
    {
        $request = $this->getContext()?->getRequest();
        if (!$request) return;

        // Purchase[...] payload
        $payload = $request->request->all('Purchase');
        if (!is_array($payload) || !array_key_exists('linesSpreadsheet', $payload)) {
            return; // якщо поля нема — нічого не робимо (manual режим)
        }

        $json = (string)($payload['linesSpreadsheet'] ?? '[]');
        $rows = json_decode($json, true);
        if (!is_array($rows)) $rows = [];

        // 1) існуючі REQUEST lines по requestItemId
        $existingByReqItemId = [];
        foreach ($purchase->getLines() as $line) {
            if (!$line instanceof PurchaseLine) continue;
            if ($line->getSourceType() === 'REQUEST' && $line->getRequestItemId()) {
                $existingByReqItemId[(int)$line->getRequestItemId()] = $line;
            }
        }

        $seenReqItemIds = [];

        // 2) обробляємо рядки таблиці
        foreach ($rows as $r) {
            if (!is_array($r)) continue;

            $requestItemId = isset($r['requestItemId']) ? (int)$r['requestItemId'] : 0;
            $itemId        = isset($r['itemId']) ? (int)$r['itemId'] : 0;

            // qty може бути string "1.000"
            $buyQtyRaw = (string)($r['buyQty'] ?? '0.000');
            $buyQtyVal = (float) str_replace(',', '.', $buyQtyRaw);

            // Це "рядок з заявок". Якщо requestItemId=0 — пропускаємо (manual робиться через CRUD)
            if ($requestItemId <= 0) continue;

            // Поки Item не вибраний або qty=0 — не створюємо/не оновлюємо line
            if ($itemId <= 0 || $buyQtyVal <= 0) {
                continue;
            }

            $seenReqItemIds[$requestItemId] = true;

            $line = $existingByReqItemId[$requestItemId] ?? new PurchaseLine();

            // якщо новий
            if (!$line->getId()) {
                $line->setPurchase($purchase);
                $purchase->addLine($line);
            }

            // Маркуємо як REQUEST
            $line->setSourceType('REQUEST');
            $line->setRequestItemId($requestItemId);

            // item + qty
            $line->setItem($this->em->getReference(Item::class, $itemId));
            $line->setQty(number_format($buyQtyVal, 3, '.', ''));

            // warehouse у line можна лишати null, тоді підхопиться з purchase->warehouse при posting

            // 3) allocation: 1 allocation на 1 requestItemId
            $alloc = null;
            foreach ($line->getAllocations() as $a) {
                if ($a instanceof PurchaseLineRequestItemAllocation
                    && $a->getRequestItem()?->getId() === $requestItemId
                ) {
                    $alloc = $a;
                    break;
                }
            }

            if (!$alloc) {
                $alloc = new PurchaseLineRequestItemAllocation();
                $alloc->setRequestItem($this->em->getReference(PartRequestItem::class, $requestItemId));
                $line->addAllocation($alloc);
            }

            $alloc->setQty(number_format($buyQtyVal, 3, '.', ''));
        }

        // 4) Прибираємо REQUEST-lines, яких більше нема в spreadsheet
        //    (MANUAL не чіпаємо)
        foreach ($purchase->getLines()->toArray() as $line) {
            if (!$line instanceof PurchaseLine) continue;
            if ($line->getSourceType() !== 'REQUEST') continue;

            $rid = $line->getRequestItemId();
            if ($rid && !isset($seenReqItemIds[$rid])) {
                $purchase->removeLine($line);
            }
        }
    }


}
