<?php

namespace App\Admin\Filter;

use App\Entity\Vehicle;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

final class VehicleAnyFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, string $label = 'Авто'): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(EntityType::class)
            ->setFormTypeOption('class', Vehicle::class)
            ->setFormTypeOption('choice_label', static fn (Vehicle $v) => (string) $v)
            ->setFormTypeOption('placeholder', '— будь-яке —')
            ->setFormTypeOption('required', false);
    }

    public function apply(
        QueryBuilder $qb,
        FilterDataDto $filterDataDto,
        ?FieldDto $fieldDto,
        EntityDto $entityDto
    ): void {
        $vehicle = $filterDataDto->getValue();
        if (!$vehicle instanceof Vehicle) {
            return;
        }

        $root = $qb->getRootAliases()[0] ?? 'entity';

        // ✅ унікальні alias під кожен фільтр (щоб не конфліктувати з createIndexQueryBuilder)
        $p = preg_replace('/\W+/', '_', (string) $filterDataDto->getProperty());
        $itemsAlias = 'f_' . $p . '_items';
        $itemsVehAlias = 'f_' . $p . '_items_vehicle';
        $defVehAlias = 'f_' . $p . '_default_vehicle';

        $this->ensureLeftJoin($qb, $root . '.items', $itemsAlias);
        $this->ensureLeftJoin($qb, $itemsAlias . '.vehicle', $itemsVehAlias);
        $this->ensureLeftJoin($qb, $root . '.defaultVehicle', $defVehAlias);

        $qb->andWhere(sprintf('(%s = :veh OR %s = :veh)', $defVehAlias, $itemsVehAlias))
            ->setParameter('veh', $vehicle)
            ->distinct();
    }

    private function ensureLeftJoin(QueryBuilder $qb, string $join, string $alias): void
    {
        // якщо alias вже існує — не додаємо
        foreach ((array) $qb->getDQLPart('join') as $joins) {
            foreach ($joins as $j) {
                if ($j->getAlias() === $alias) {
                    return;
                }
            }
        }

        $qb->leftJoin($join, $alias);
    }
}
