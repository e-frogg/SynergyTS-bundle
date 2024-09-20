<?php

namespace Efrogg\Synergy\AutoSync;

use Efrogg\Synergy\AutoSync\Persister\AutoSyncPersisterInterface;
use Efrogg\Synergy\Data\Criteria;
use Efrogg\Synergy\Data\SearchResult;

class autoSyncService
{

    public function __construct(
        private readonly AutoSyncPersisterInterface $autoSyncPersister
    ) {
    }

    public function initAutoSync(string $entityClass, Criteria $criteria, SearchResult $searchResult): AutoSync
    {
        //TODO : convertir les criteria en plusieurs criterias pour chaque association
        // il sera aors possible, pour chaque entité mise a jour / ajoutée / supprimée de savoir si elle est concernée par ce Criteria
        // et donc de publier un message sur le bon topic
        $autoSync = new AutoSync($this->buildTopicName());
        $autoSync->setCriteriaCollection($this->buildCriteriaCollection($entityClass, $criteria, $searchResult));
        $this->autoSyncPersister->persist($autoSync);
        return $autoSync;
    }

    private function buildTopicName(): string
    {
        return uniqid('autoSync', true);
    }

    /**
     * @return array<string, Criteria>
     */
    private function buildCriteriaCollection(string $entityClass, Criteria $criteria, SearchResult $searchResult): array
    {
        $criteriaCollection = [];
        //TODO : convertir le Criteria
        $criteriaCollection[$entityClass]= $this->toSimpleCriteria($criteria);
        foreach ($criteria->getAssociations() as $associationName => $associationCriteria) {
            //TODO : convertir le Criteria
            // ajouter un filtre sur les id etc...
//            $criteriaCollection = [$criteriaCollection, ...($this->buildCriteriaCollection($associationName, $associationCriteria, $searchResult))];
        }
        return $criteriaCollection;
    }

    /**
     * @param Criteria $criteria
     *
     * @return Criteria
     */
    private function toSimpleCriteria(Criteria $criteria): Criteria
    {
        return new Criteria($criteria->getFilters());
    }
}
