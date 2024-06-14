<?php

namespace Efrogg\Synergy\Data;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Query\Expr\OrderBy;
use Doctrine\ORM\QueryBuilder;
use Efrogg\Synergy\Acl\AclManager;
use Efrogg\Synergy\Entity\SynergyEntityInterface;
use Efrogg\Synergy\Exception\GrantException;
use Efrogg\Synergy\Helper\EntityHelper;
use LogicException;
use ReflectionException;
use RuntimeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;

class EntityRepositoryHelper
{

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EntityHelper $entityHelper,
        private readonly ClassMetadataFactoryInterface $classMetadataFactory,
        private readonly PropertyAccessorInterface $propertyAccessor,
        private readonly AclManager $aclManager,
    ) {
    }

    /**
     * Finds entities by a set of criteria.
     *
     * @psalm-param class-string<SynergyEntityInterface> $entityClass
     * @throws ReflectionException
     * @throws GrantException
     */
    public function search(string $entityClass, Criteria $criteria, bool $isMain = true): SearchResult
    {
        $this->aclManager->checkClassIsGranted($entityClass, AclManager::READ);
        $lastMainIds = [];

        if (!is_a($entityClass, SynergyEntityInterface::class, true)) {
            throw new LogicException(__CLASS__ . ' : only SynergyEntityInterface can be searched');
        }

        $queryBuilder = $this->createQueryBuilder($entityClass,$criteria);
        if ($queryBuilder instanceof QueryBuilder) {

            $mainResult = $queryBuilder->getQuery()->getResult();
        } else {
            $filters = $criteria->getFilters();
            $mainResult = $this->entityManager->getRepository($entityClass)->findBy(
                $filters,
                $criteria->getOrderBy(),
                $criteria->getLimit(),
                $criteria->getOffset()
            );
        }

        // un essai, à approfondir
//        $query = $this->createQueryBuilder($entityClass,$criteria);
//        $mainResult = $query->getQuery()->getResult();

        // filter only allowed entities
        $mainResult = array_filter($mainResult, fn(SynergyEntityInterface $entity) => $this->aclManager->isEntityGranted($entity, AclManager::READ));

        if ($isMain) {
            $entityName = $this->entityHelper->findEntityName($entityClass) ?? throw new RuntimeException('entity name for ['.$entityClass.'] not found');
            $lastMainIds[$entityName] = array_filter(array_map(static fn(SynergyEntityInterface $entity) => $entity->getId(), $mainResult));
        }
        $results = [$mainResult];


        $metaData = $this->classMetadataFactory->getMetadataFor($entityClass);

        foreach ($criteria->getAssociations() as $associationPropertyName => $baseAssociationCriteria) {
            $associationCriteria = clone $baseAssociationCriteria;

            $propertyInfo = $metaData->getReflectionClass()->getProperty($associationPropertyName);
            $attributeInstance = null;

            $filterKey = null;
            $filterValue = null;

            foreach ($propertyInfo->getAttributes() as $attribute) {
                if (is_a($attribute->getName(), ManyToMany::class, true)
                    || is_a($attribute->getName(), OneToMany::class, true)) {
                    /** @var ManyToMany|OneToMany $attributeInstance */
                    $attributeInstance = new ($attribute->getName())(...$attribute->getArguments());
                    $mappedBy = $attributeInstance->mappedBy ?? throw new LogicException(
                        'ManyToMany or OneToMany relation must have "mappedBy" to work as synergy association'
                    );


                    $filterKey = $mappedBy;              // ex : work
                    $filterValue = $mainResult;          // ex : User

                    // ex : $targetEntityClass = Work
                    //      $entity : User
                    //      $filterKey = 'user'
                    //      $filterValue = [User,User...]
                    //  => search Work by User

                    break;  // relation has been found
                }

                if (is_a($attribute->getName(), ManyToOne::class, true)
                    || is_a($attribute->getName(), OneToOne::class, true)) {
                    /** @var ManyToOne|OneToOne $attributeInstance */
                    $attributeInstance = new ($attribute->getName())(...$attribute->getArguments());

                    // extract target entities ids
                    $ids = array_map(function ($entity) use ($associationPropertyName) {
                        if (!$entity instanceof SynergyEntityInterface) {
                            throw new LogicException('only SynergyInterface is allowed to be source of association');
                        }
                        // ex : $entity : Work
                        //      $associationPropertyName = "category"
                        //      targetEntity : Category
                        $targetEntity = $this->propertyAccessor->getValue($entity, $associationPropertyName);
                        if (null !== $targetEntity && !$targetEntity instanceof SynergyEntityInterface) {
                            throw new LogicException('only SynergyInterface is allowed to be target of association');
                        }
                        return $targetEntity?->getId();
                    }, $mainResult);

                    $ids = array_filter($ids);
                    if (empty($ids)) {
                        continue 2; // next association
                    }

                    $filterKey = 'id';
                    $filterValue = $ids;

                    // ex : $entity : Work
                    //      $targetEntityClass = Category
                    //      $filterKey = 'id'
                    //      $filterValue = [id, id ....]
                    //  => search Category by id

                    break;              // relation has been found
                }
            }

            if(!isset($filterKey, $filterValue)) {
                throw new RuntimeException("No relationship found for $associationPropertyName");
            }


//            dump((new ReflectionClass($attributeInstance))->getShortName());

            $targetEntityClass = $attributeInstance->targetEntity ?? throw new RuntimeException(
                'no targetEntity found for association ' . $associationPropertyName.'. please precise it in the attribute definition'
            );
            if (!is_a($targetEntityClass, SynergyEntityInterface::class, true)) {
                throw new RuntimeException(sprintf('association %s is not a Synergy entity', $targetEntityClass));
            }

            if($associationCriteria->getLimit() !== null && is_iterable($filterValue)) {
                // fetching association with limit for each item
                // bad performances, but it's the only way to do it easily
                foreach ($filterValue as $itemValue) {
                    $perItemCriteria = (clone $associationCriteria)->addFilter($filterKey, $itemValue);
                    $results[] = $this->search($targetEntityClass, $perItemCriteria, false)->getEntities();
                }
            }  else {
                $associationCriteria->addFilter($filterKey, $filterValue);
                $results[] = $this->search($targetEntityClass, $associationCriteria, false)->getEntities();
            }
        }
        return new SearchResult(array_merge(...$results), $lastMainIds);
    }


    /**
     * @param class-string<SynergyEntityInterface> $entityClass
     * @param Criteria                    $criteria
     *
     * @return \Doctrine\ORM\QueryBuilder
     * @deprecated : sujet a approfondir éventuellement
     */
    protected function createQueryBuilder(string $entityClass, Criteria $criteria): ?QueryBuilder
    {
        // Criteria already has a query builder
        $qb = $criteria->getQueryBuilder();
        if(null === $qb) {
            // pour le moment, on ne sait pas crée un query builder à partir de Filters
            return null;
        }

        // un essai, à approfondir, pour le moment on ne l'utilise pas
//        $qb = $this->entitymanager->getrepository($entityclass)->createquerybuilder('m');
//
//        $qb->select('m');
//        $qb->join(user::class, 'u');
//        // filters
//        $and = $qb->expr()->andx();
//        foreach ($criteria->getfilters() as $key => $value) {
//            if (is_array($value)) {
//                $and->add($qb->expr()->in($key, $value));
//            } else {
//                $and->add($qb->expr()->eq($key, $value));
//            }
//        }
//        $qb->where($and);

        $rootAlias = $qb->getRootAliases()[0]??'';
        // orderBy
        foreach ($criteria->getOrderBy()??[] as $sortKey => $sortDirection) {
            // add alias if needed
            // nope ! on ne sait pas si c'est un alias ou pas
//            if(!str_contains($sortKey, '.') && $rootAlias !== '') {
//                $sortKey = $rootAlias.'.' . $sortKey;
//            }
            $qb->orderBy(new OrderBy($sortKey, $sortDirection));
        }

        // limit
        $qb->setMaxResults($criteria->getLimit());
        $qb->setFirstResult($criteria->getOffset());

//        dd($qb->getQuery()->getSQL());
        return $qb;

    }

}
