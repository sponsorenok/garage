<?php

namespace App\Controller\Admin;

use App\Entity\InventoryTransaction;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use Doctrine\ORM\QueryBuilder;
class InventoryTransactionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return InventoryTransaction::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Рух складу')
            ->setEntityLabelInPlural('Рух складу')
            ->setPageTitle(Crud::PAGE_INDEX, 'Рух складу')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }
    public function configureFields(string $pageName): iterable
    {
        return [
            DateTimeField::new('createdAt', 'Дата/час'),
            AssociationField::new('warehouse', 'Склад'),
            AssociationField::new('item', 'Номенклатура'),
            NumberField::new('qtyChange', 'Кількість (±)'),
            TextField::new('type', 'Тип'),
            TextareaField::new('note', 'Примітка')->hideOnIndex(),
            TextField::new('refType', 'Джерело')->hideOnIndex(),
            NumberField::new('refId', 'ID')->hideOnIndex(),
        ];
    }
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('warehouse', 'Склад'))
            ->add(EntityFilter::new('item', 'Номенклатура'))
            ->add(DateTimeFilter::new('createdAt', 'Дата'));
    }
    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $req = $this->getContext()?->getRequest();
        $params = $req?->query->all('filters') ?? [];

        if (!empty($params['warehouse']['value'])) {
            $qb->andWhere('entity.warehouse = :warehouse')
                ->setParameter('warehouse', (int)$params['warehouse']['value']);
        }

        if (!empty($params['item']['value'])) {
            $qb->andWhere('entity.item = :item')
                ->setParameter('item', (int)$params['item']['value']);
        }

        return $qb;
    }
}
