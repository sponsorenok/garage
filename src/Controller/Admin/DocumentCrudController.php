<?php

namespace App\Controller\Admin;

use App\Entity\Document;
use App\Entity\DocumentType;
use App\Entity\VehicleAssignment;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
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
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setSearchFields([
                'title',
                'docNumber',
                'fileName',
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
            ->add(TextFilter::new('docNumber', '№'))
            ->add(DateTimeFilter::new('createdAt', 'Створено'));
    }

    public function createEntity(string $entityFqcn)
    {
        $doc = new Document();

        $context = $this->getContext();
        $req = $context?->getRequest();

        $assignmentId = $req?->query->get('assignmentId');
        if ($assignmentId) {
            $assignment = $this->em->getRepository(VehicleAssignment::class)->find((int)$assignmentId);
            if ($assignment) {
                $doc->setAssignment($assignment);
                $doc->setVehicle($assignment->getVehicle());
                $doc->setDepartment($assignment->getDepartment());

                $type = $this->em->getRepository(DocumentType::class)->findOneBy(['code' => 'VEHICLE_ASSIGN']);
                if ($type) $doc->setType($type);

                $doc->setTitle('Підстава призначення');
            }
        }

        $vehicleId = $req?->query->get('vehicleId');
        if ($vehicleId) {
            $vehicle = $this->em->getRepository(\App\Entity\Vehicle::class)->find((int)$vehicleId);
            if ($vehicle) {
                $doc->setVehicle($vehicle);
                $doc->setDepartment($vehicle->getDepartment());
                if (!$doc->getTitle()) $doc->setTitle('Документ по авто');
            }
        }

        $purchaseId = $req?->query->get('purchaseId');
        if ($purchaseId) {
            $purchase = $this->em->getRepository(\App\Entity\Purchase::class)->find((int)$purchaseId);
            if ($purchase) {
                $doc->setPurchase($purchase);
                // контекстні поля можна НЕ дублювати, але якщо хочеш:
                // $doc->setDepartment(null);
                // $doc->setVehicle(null);

                $type = $this->em->getRepository(DocumentType::class)->findOneBy(['code' => 'PURCHASE_DOC']);
                if ($type) $doc->setType($type);

                if (!$doc->getTitle()) $doc->setTitle('Документ закупівлі');
            }
        }



        return $doc;
    }

    public function configureFields(string $pageName): iterable
    {
        // ===== INDEX =====
        yield TextField::new('title', 'Назва')->onlyOnIndex();
        yield AssociationField::new('department', 'Підрозділ')->onlyOnIndex();
        yield AssociationField::new('vehicle', 'Авто')->onlyOnIndex();
        yield AssociationField::new('type', 'Тип')->onlyOnIndex();
        yield TextField::new('docNumber', '№')->onlyOnIndex();
        yield DateField::new('docDate', 'Дата')->onlyOnIndex();

        yield Field::new('fileName', 'Файл')
            ->setTemplatePath('admin/document/_file_link.html.twig')
            ->onlyOnIndex();

        // ===== FORMS =====
        yield AssociationField::new('type', 'Тип')->onlyOnForms();

        yield AssociationField::new('assignment', 'Подія')
            ->setHelp('Документ привʼязується до події призначення авто.')
            ->onlyOnForms();

        yield AssociationField::new('department', 'Підрозділ')->onlyOnForms();
        yield AssociationField::new('vehicle', 'Авто')->onlyOnForms();

        yield TextField::new('title', 'Назва')->onlyOnForms();
        yield TextField::new('docNumber', '№')->onlyOnForms();
        yield DateField::new('docDate', 'Дата')->onlyOnForms();

        yield Field::new('file', 'Файл')
            ->setFormType(\Vich\UploaderBundle\Form\Type\VichFileType::class)
            ->setFormTypeOptions([
                'required' => false, // ✅ було true
                'download_uri' => false,
                'allow_delete' => false,
            ])
            ->onlyOnForms();
    }

    // опційно: зробимо кнопку "Повернутись" або "Cancel" через returnTo (якщо хочеш — скажи)
}
