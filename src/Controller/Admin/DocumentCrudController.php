<?php

namespace App\Controller\Admin;

use App\Entity\Document;
use App\Entity\DocumentType;
use App\Entity\Purchase;
use App\Entity\Vehicle;
use App\Entity\VehicleAssignment;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Vich\UploaderBundle\Form\Type\VichFileType;

final class DocumentCrudController extends AbstractCrudController
{
    public function __construct(private EntityManagerInterface $em) {}

    public static function getEntityFqcn(): string
    {
        return Document::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Документ')
            ->setEntityLabelInPlural('Документи')
            ->setPageTitle(Crud::PAGE_INDEX, 'Менеджер документів')
            ->setPageTitle(Crud::PAGE_NEW, 'Завантажити документ')
            ->setPageTitle(Crud::PAGE_EDIT, 'Редагувати документ')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->overrideTemplate('crud/new', 'admin/document/new.html.twig')
            ->overrideTemplate('crud/edit', 'admin/document/edit.html.twig')
            ->setSearchFields([
                'title',
                'docNumber',
                'fileName',
                'type.code',
                'type.name',
                'department.name',
                'department.code',
                'vehicle.plate',
                'vehicle.vin',
            ]);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('type', 'Тип'))
            ->add(EntityFilter::new('department', 'Підрозділ'))
            ->add(EntityFilter::new('vehicle', 'Авто'))
            ->add(EntityFilter::new('assignment', 'Подія'))
            ->add(EntityFilter::new('purchase', 'Закупівля'))
            ->add(TextFilter::new('docNumber', '№'))
            ->add(DateTimeFilter::new('docDate', 'Дата документа'))
            ->add(DateTimeFilter::new('createdAt', 'Створено'));
    }

    public function createEntity(string $entityFqcn): Document
    {
        $doc = new Document();
        $req = $this->getContext()?->getRequest();

        $documentTypeCode = $req?->query->get('documentTypeCode') ?: $req?->query->get('typeCode');
        if ($documentTypeCode) {
            $type = $this->em->getRepository(DocumentType::class)->findOneBy(['code' => $documentTypeCode]);
            if ($type) {
                $doc->setType($type);
                $doc->setTitle($type->getName());
            }
        }

        $assignmentId = $req?->query->get('assignmentId');
        if ($assignmentId) {
            $assignment = $this->em->getRepository(VehicleAssignment::class)->find((int) $assignmentId);
            if ($assignment) {
                $doc->setAssignment($assignment);
                $doc->setVehicle($assignment->getVehicle());
                $doc->setDepartment($assignment->getDepartment());

                if (!$doc->getType()) {
                    $type = $this->em->getRepository(DocumentType::class)->findOneBy(['code' => 'VEHICLE_ASSIGN']);
                    if ($type) {
                        $doc->setType($type);
                    }
                }

                if (!$doc->getTitle()) {
                    $doc->setTitle('Підстава призначення');
                }
            }
        }

        $vehicleId = $req?->query->get('vehicleId');
        if ($vehicleId) {
            $vehicle = $this->em->getRepository(Vehicle::class)->find((int) $vehicleId);
            if ($vehicle) {
                $doc->setVehicle($vehicle);
                $doc->setDepartment($vehicle->getDepartment());
                if (!$doc->getTitle()) {
                    $doc->setTitle('Документ по авто');
                }
            }
        }

        $purchaseId = $req?->query->get('purchaseId');
        if ($purchaseId) {
            $purchase = $this->em->getRepository(Purchase::class)->find((int) $purchaseId);
            if ($purchase) {
                $doc->setPurchase($purchase);

                if (!$doc->getType()) {
                    $type = $this->em->getRepository(DocumentType::class)->findOneBy(['code' => 'PURCHASE_DOC']);
                    if ($type) {
                        $doc->setType($type);
                    }
                }

                if (!$doc->getTitle()) {
                    $doc->setTitle('Документ закупівлі');
                }
            }
        }

        return $doc;
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::persistEntity($entityManager, $entityInstance);

        if ($entityInstance instanceof Document) {
            $this->assignVehiclePassportDocument($entityManager, $entityInstance);
        }
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::updateEntity($entityManager, $entityInstance);

        if ($entityInstance instanceof Document) {
            $this->assignVehiclePassportDocument($entityManager, $entityInstance);
        }
    }

    private function assignVehiclePassportDocument(EntityManagerInterface $entityManager, Document $document): void
    {
        $vehicle = $document->getVehicle();
        $typeCode = $document->getType()?->getCode();

        if (!$vehicle || 'PASSPORT_VEHICLE' !== $typeCode) {
            return;
        }

        $vehicle->setPassportForm($document);
        $entityManager->persist($vehicle);
        $entityManager->flush();
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('title', 'Назва')->onlyOnIndex();
        yield AssociationField::new('type', 'Тип')->onlyOnIndex();
        yield TextField::new('docNumber', '№')->onlyOnIndex();
        yield DateField::new('docDate', 'Дата')->onlyOnIndex();
        yield AssociationField::new('vehicle', 'Авто')->onlyOnIndex();
        yield AssociationField::new('department', 'Підрозділ')->onlyOnIndex();

        yield Field::new('fileName', 'Файл')
            ->setTemplatePath('admin/document/_file_link.html.twig')
            ->onlyOnIndex();

        yield FormField::addFieldset('Файл та тип', 'fa fa-file-arrow-up')->onlyOnForms();
        yield AssociationField::new('type', 'Тип документа')
            ->setRequired(true)
            ->setHelp('Оберіть існуючий тип документа. У списку показано назву і code.')
            ->setQueryBuilder(fn (QueryBuilder $qb) => $qb
                ->andWhere('entity.isActive = :active')
                ->setParameter('active', true)
                ->orderBy('entity.name', 'ASC'))
            ->setColumns(6)
            ->onlyOnForms();
        yield Field::new('file', 'Файл')
            ->setFormType(VichFileType::class)
            ->setFormTypeOptions([
                'required' => Crud::PAGE_NEW === $pageName,
                'download_uri' => false,
                'allow_delete' => false,
            ])
            ->setColumns(6)
            ->onlyOnForms();

        yield FormField::addFieldset('Реквізити', 'fa fa-clipboard-list')->onlyOnForms();
        yield TextField::new('title', 'Назва')
            ->setColumns(6)
            ->onlyOnForms();
        yield TextField::new('docNumber', 'Номер')
            ->setColumns(3)
            ->onlyOnForms();
        yield DateField::new('docDate', 'Дата')
            ->setColumns(3)
            ->onlyOnForms();

        yield FormField::addFieldset('Звʼязки', 'fa fa-link')->onlyOnForms();
        yield AssociationField::new('vehicle', 'Авто')
            ->setRequired(false)
            ->setColumns(6)
            ->onlyOnForms();
        yield AssociationField::new('department', 'Підрозділ')
            ->setRequired(false)
            ->setColumns(6)
            ->onlyOnForms();
        yield AssociationField::new('assignment', 'Подія призначення')
            ->setRequired(false)
            ->setHelp('Опційно: документ привʼязується до події призначення авто.')
            ->setColumns(6)
            ->onlyOnForms();
        yield AssociationField::new('purchase', 'Закупівля')
            ->setRequired(false)
            ->setColumns(6)
            ->onlyOnForms();
    }
}
