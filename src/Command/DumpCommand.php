<?php

namespace Efrogg\Synergy\Command;

use App\Entity\User;
use Efrogg\Synergy\Data\Criteria;
use Efrogg\Synergy\Data\EntityRepositoryHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'Synergy:test',
    description: 'Test command')]
class DumpCommand extends Command
{
    public function __construct(private readonly EntityRepositoryHelper $entityRepositoryHelper)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Test command')
            ->setHelp('This command is just for testing purposes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Test command executed');

        $criteria = new Criteria(['id' => [2, 3]]);
        $workAssociation = $criteria->getAssociation('works')
                                    ->setOrderBy(['id' => 'DESC'])
                                    ->setLimit(2);                 // problème, ça ne fetch qu'un seul work en tout, et pas un par user
        $categoryCriteria = $workAssociation
            ->getAssociation('category')
            ->addAssociation('project');
        $categoryCriteria
            ->getAssociation('parent')
            ->getAssociation('parent')
            ->getAssociation('parent')
            ->getAssociation('parent');
        //        $criteria->getAssociation("dailies")
        //                 ->setLimit(10);

        $result = $this->entityRepositoryHelper->search(User::class, $criteria);

        foreach ($result->getEntities() as $synergyEntity) {
            dump($synergyEntity::class.' : '.$synergyEntity->getId());
        }

        return self::SUCCESS;
    }
}
