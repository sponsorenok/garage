<?php

namespace App\Controller\Admin;

use App\Entity\Department;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;

final class DepartmentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Department::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Підрозділ')
            ->setEntityLabelInPlural('Підрозділи');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();

        // index: indented
        yield TextField::new('treeName', 'Назва')->onlyOnIndex();

        // forms/detail: normal
        yield TextField::new('name', 'Назва')->hideOnIndex();

        yield TextField::new('code', 'Код')->hideOnIndex();

        yield AssociationField::new('parent', 'Головний підрозділ')
            ->setRequired(false);

        yield CollectionField::new('staffSlots', 'Штатні автівки')
            ->useEntryCrudForm(DepartmentVehicleSlotCrudController::class)
            ->setFormTypeOptions(['by_reference' => false])
            ->renderExpanded()
            ->allowAdd(true)
            ->allowDelete(true)
            ->onlyOnForms();

        yield Field::new('staffingBtn', '')
            ->setTemplatePath('admin/department/_completeness_button.html.twig')
            ->setCssClass('text-nowrap')
            ->onlyOnIndex();

    }

    public function configureActions(Actions $actions): Actions
    {
//        $staffing = Action::new('staffing', 'Комплектність')
//            ->setIcon('fa fa-truck')
//            ->linkToUrl(function ($entity) {
//                return $this->container
//                    ->get(AdminUrlGenerator::class)
//                    ->setRoute('admin_department_staffing', [
//                        'id' => $entity->getId(),
//                    ])
//                    ->generateUrl();
//            });
//
        return $actions;
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $alias = $qb->getRootAliases()[0];

        $qb->leftJoin($alias . '.parent', 'p')
            ->addSelect('p')

            // 1️⃣ parents before children (NULL parent first)
            ->addOrderBy($alias . '.parent', 'ASC')

            // 2️⃣ group children under parent name
            ->addOrderBy('p.name', 'ASC')

            // 3️⃣ alphabetical inside group
            ->addOrderBy($alias . '.name', 'ASC');

        return $qb;
    }
}
