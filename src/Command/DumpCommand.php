<?php

namespace Efrogg\Synergy\Command;

use App\Entity\Project;
use App\Entity\User;
use Efrogg\Synergy\Data\Criteria;
use Efrogg\Synergy\Data\EntityRepositoryHelper;
use Efrogg\Synergy\Helper\EntityHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'synergy:dump',
    description: 'Dump entities configurations'
)]
class DumpCommand extends Command
{
    public function __construct(private readonly EntityRepositoryHelper $entityRepositoryHelper, private readonly EntityHelper $entityHelper)
    {
        parent::__construct();
    }

    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        foreach ($this->entityHelper->getEntityClasses() as $entityClass) {
            $io->writeln(sprintf('Entity: <fg=yellow>%s</> (%s)', EntityHelper::getEntityName($entityClass), $entityClass));
        }

        $criteria = new Criteria([2, 3]);
        //        $workAssociation = $criteria->getAssociation("releases")
        //                                    ->setOrderBy(['id' => 'DESC'])
        //                                    ->setLimit(2);                 // problème, ça ne fetch qu'un seul work en tout, et pas un par user
        //        $categoryCriteria = $workAssociation
        //            ->getAssociation("category")
        //            ->addAssociation('project');
        //        $categoryCriteria
        //            ->getAssociation('parent')
        //            ->getAssociation('parent')
        //            ->getAssociation('parent')
        //            ->getAssociation('parent');
        //        $criteria->getAssociation("dailies")
        //                 ->setLimit(10);

        $result = $this->entityRepositoryHelper->search(Project::class, $criteria);

        foreach ($result->getEntities() as $synergyEntity) {
            dump($synergyEntity::class.' : '.$synergyEntity->getId());
        }

        return self::SUCCESS;
    }
}
