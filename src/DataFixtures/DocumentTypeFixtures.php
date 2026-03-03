<?php

namespace App\DataFixtures;

use App\Entity\DocumentType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class DocumentTypeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $items = [
            ['VEHICLE_ASSIGN', 'Підстава призначення авто'],
            ['STOCK_RECEIPT', 'Документ приходу залишків'],
            ['STOCK_TRANSFER', 'Документ переміщення залишків'],
        ];

        foreach ($items as [$code, $name]) {
            $dt = new DocumentType();
            $dt->setCode($code);
            $dt->setName($name);
            $dt->setIsActive(true);
            $manager->persist($dt);
        }

        $manager->flush();
    }
}
