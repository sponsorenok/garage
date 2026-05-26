<?php

namespace App\Controller\Admin;

use App\Entity\Document;
use App\Entity\Vehicle;
use App\Entity\VehicleImage;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Component\Form\Extension\Core\Type\FileType;

final class VehicleImageCrudController extends AbstractCrudController
{
    public function __construct(private EntityManagerInterface $em) {}

    public static function getEntityFqcn(): string
    {
        return VehicleImage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Фото авто')
            ->setEntityLabelInPlural('Фото авто')
            ->setDefaultSort(['eventDate' => 'DESC', 'createdAt' => 'DESC'])
            ->setSearchFields(['title', 'notes', 'fileName', 'vehicle.plate', 'vehicle.vin', 'document.title']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('vehicle', 'Авто'))
            ->add(EntityFilter::new('document', 'Документ'));
    }

    public function createEntity(string $entityFqcn): VehicleImage
    {
        $image = new VehicleImage();
        $request = $this->getContext()?->getRequest();

        $vehicleId = $request?->query->get('vehicleId');
        if ($vehicleId) {
            $vehicle = $this->em->getRepository(Vehicle::class)->find((int) $vehicleId);
            if ($vehicle) {
                $image->setVehicle($vehicle);
                $image->setOdometerKm($vehicle->getCurrentOdometerKm());
            }
        }

        $documentId = $request?->query->get('documentId');
        if ($documentId) {
            $document = $this->em->getRepository(Document::class)->find((int) $documentId);
            if ($document) {
                $image->setDocument($document);
                $documentVehicle = $document->getVehicle() ?: $document->getAssignment()?->getVehicle();
                $image->setVehicle($documentVehicle ?: $image->getVehicle());
                $image->setEventDate($document->getDocDate());
                $image->setTitle($document->getTitle());
            }
        }

        return $image;
    }

    public function configureFields(string $pageName): iterable
    {
        yield Field::new('fileName', 'Фото')
            ->setTemplatePath('admin/vehicle_image/_image_thumb.html.twig')
            ->onlyOnIndex();

        yield AssociationField::new('vehicle', 'Авто')
            ->setRequired(true);
        yield AssociationField::new('document', 'Документ')
            ->setRequired(false)
            ->setHelp('Опційно: привʼязати фото до документа авто.');
        yield DateField::new('eventDate', 'Дата події')
            ->setHelp('Дата, до якої належить фото. Може бути датою документа або огляду.');
        yield IntegerField::new('odometerKm', 'Показник одометра, км')
            ->hideOnIndex();
        yield TextField::new('title', 'Назва')
            ->hideOnIndex();
        yield TextareaField::new('notes', 'Нотатки')
            ->hideOnIndex();

        yield Field::new('imageFiles', 'Файли фото')
            ->setFormType(FileType::class)
            ->setFormTypeOptions([
                'required' => $pageName === Crud::PAGE_NEW,
                'mapped' => false,
                'multiple' => true,
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/webp',
                ],
            ])
            ->setHelp('Можна вибрати одне або кілька фото. Всі вони отримають ці самі дату, пробіг і документ.')
            ->onlyOnForms();
    }
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof VehicleImage) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        $files = $this->getUploadedImageFiles();
        if (!$files) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        foreach ($files as $index => $file) {
            $image = $index === 0 ? $entityInstance : $this->copyImageContext($entityInstance);
            $image->setImageFile($file);
            $entityManager->persist($image);
        }

        $entityManager->flush();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof VehicleImage) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        $files = $this->getUploadedImageFiles();
        foreach ($files as $file) {
            $image = $this->copyImageContext($entityInstance);
            $image->setImageFile($file);
            $entityManager->persist($image);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile[]
     */
    private function getUploadedImageFiles(): array
    {
        $request = $this->getContext()?->getRequest();
        if (!$request) {
            return [];
        }

        return $this->findUploadedFilesByField($request->files->all(), 'imageFiles');
    }

    /**
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile[]
     */
    private function findUploadedFilesByField(array $files, string $fieldName): array
    {
        foreach ($files as $key => $value) {
            if ($key === $fieldName) {
                return is_array($value) ? array_values(array_filter($value)) : [];
            }

            if (is_array($value)) {
                $nested = $this->findUploadedFilesByField($value, $fieldName);
                if ($nested) {
                    return $nested;
                }
            }
        }

        return [];
    }

    private function copyImageContext(VehicleImage $source): VehicleImage
    {
        $image = new VehicleImage();
        $image->setVehicle($source->getVehicle());
        $image->setDocument($source->getDocument());
        $image->setEventDate($source->getEventDate());
        $image->setOdometerKm($source->getOdometerKm());
        $image->setTitle($source->getTitle());
        $image->setNotes($source->getNotes());

        return $image;
    }

}
