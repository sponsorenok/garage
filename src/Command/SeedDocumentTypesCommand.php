<?php

namespace App\Command;

use App\Entity\DocumentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:seed:document-types',
    description: 'Upsert базових типів документів (без очищення БД).'
)]
final class SeedDocumentTypesCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $items = [
            ['VEHICLE_ASSIGN', 'Підстава призначення авто'],
            ['STOCK_RECEIPT', 'Документ приходу залишків'],
            ['STOCK_TRANSFER', 'Документ переміщення залишків'],
        ];

        $repo = $this->em->getRepository(DocumentType::class);

        $created = 0;
        $updated = 0;

        foreach ($items as [$code, $name]) {
            /** @var DocumentType|null $dt */
            $dt = $repo->findOneBy(['code' => $code]);

            if (!$dt) {
                $dt = new DocumentType();
                $dt->setCode($code);
                $created++;
            } else {
                $updated++;
            }

            $dt->setName($name);
            $dt->setIsActive(true);

            $this->em->persist($dt);
        }

        $this->em->flush();

        $output->writeln(sprintf('Done. created=%d updated=%d', $created, $updated));
        return Command::SUCCESS;
    }
}
