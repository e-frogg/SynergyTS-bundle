<?php

namespace Efrogg\Synergy\Data;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Efrogg\Synergy\Acl\AclManager;
use Efrogg\Synergy\Entity\SynergyEntityInterface;
use Efrogg\Synergy\Event\CustomFilterEvent;
use Efrogg\Synergy\Exception\GrantException;
use Efrogg\Synergy\Helper\EntityHelper;
use Psr\EventDispatcher\EventDispatcherInterface;
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
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Finds entities by a set of criteria.
     *
     * @param class-string<SynergyEntityInterface> $entityClass
     *
     * @throws \ReflectionException
     * @throws GrantException
     */
    public function search(string $entityClass, ?Criteria $criteria = null, bool $isMain = true): SearchResult
    {
        $criteria ??= new Criteria();
        $this->aclManager->checkClassIsGranted($entityClass, AclManager::READ);
        $lastMainIds = [];

        /* @phpstan-ignore-next-line */
        if (!is_a($entityClass, SynergyEntityInterface::class, true)) {
            throw new \LogicException(__CLASS__.' : only SynergyEntityInterface can be searched');
        }

        $queryBuilder = $this->createQueryBuilder($entityClass, $criteria);

        // if totalCountMode => return count
        $totalCount = null;
        //        if ($criteria->isTotalCountMode()) {

        $mainResult = $queryBuilder->getQuery()->getResult();

        if ($criteria->isTotalCountNeeded()) {
            $countQb = clone $queryBuilder;
            $countQb
                ->setMaxResults(null)
                ->setFirstResult(null)
                ->select('COUNT(1)');

            $totalCount = (int) $countQb->getQuery()->getSingleScalarResult();
        }
        //            $filters = $criteria->getFilters();
        //            $mainResult = $this->entityManager->getRepository($entityClass)->findBy(
        //                $filters,
        //                $criteria->getOrderBy(),
        //                $criteria->getLimit(),
        //                $criteria->getOffset()
        //            );

        // un essai, à approfondir
        //        $query = $this->createQueryBuilder($entityClass,$criteria);
        //        $mainResult = $query->getQuery()->getResult();

        // filter only allowed entities
        $mainResult = array_filter($mainResult, fn (SynergyEntityInterface $entity) => $this->aclManager->isEntityGranted($entity, AclManager::READ));

        if ($isMain) {
            $entityName = $this->entityHelper->findEntityName($entityClass) ?? throw new \RuntimeException('entity name for ['.$entityClass.'] not found');
            $lastMainIds[$entityName] = array_filter(array_map(static fn (SynergyEntityInterface $entity) => $entity->getId(), $mainResult));
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
                    $mappedBy = $attributeInstance->mappedBy ?? throw new \LogicException('ManyToMany or OneToMany relation must have "mappedBy" to work as synergy association');

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
                            throw new \LogicException('only SynergyInterface is allowed to be source of association');
                        }
                        // ex : $entity : Work
                        //      $associationPropertyName = "category"
                        //      targetEntity : Category
                        $targetEntity = $this->propertyAccessor->getValue($entity, $associationPropertyName);
                        if (null !== $targetEntity && !$targetEntity instanceof SynergyEntityInterface) {
                            throw new \LogicException('only SynergyInterface is allowed to be target of association');
                        }

                        return $targetEntity?->getId();
                    }, $mainResult);

                    $ids = array_unique(array_filter($ids));
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

            if (!isset($filterKey, $filterValue)) {
                throw new \RuntimeException("No relationship found for $associationPropertyName");
            }

            $targetEntityClass = $attributeInstance->targetEntity ?? throw new \RuntimeException('no targetEntity found for association '.$entityClass.'::'.$associationPropertyName.'. please precise it in the attribute definition');
            if (!is_a($targetEntityClass, SynergyEntityInterface::class, true)) {
                throw new \RuntimeException(sprintf('association %s is not a Synergy entity', $targetEntityClass));
            }

            if (null !== $associationCriteria->getLimit()) {
                // fetching association with limit for each item
                // bad performances, but it's the only way to do it easily
                foreach ($filterValue as $itemValue) {
                    $perItemCriteria = (clone $associationCriteria)->addFilter($filterKey, $itemValue);
                    $results[] = $this->search($targetEntityClass, $perItemCriteria, false)->getEntities();
                }
            } else {
                $associationCriteria->addFilter($filterKey, $filterValue);
                $results[] = $this->search($targetEntityClass, $associationCriteria, false)->getEntities();
            }
        }

        return new SearchResult(array_merge(...$results), $lastMainIds, $totalCount);
    }

    /**
     * @param class-string<SynergyEntityInterface> $entityClass
     */
    protected function createQueryBuilder(string $entityClass, Criteria $criteria): QueryBuilder
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('c')
            ->from($entityClass, 'c');

        if (!empty($criteria->getIds())) {
            $qb->andWhere($qb->expr()->in('c.id', ':ids'))
                ->setParameter('ids', $criteria->getIds());
        }

        $this->enrichQueryBuilderFilters($qb, $criteria->getFilters(), 'c');

        $this->enrichQueryBuilderOrder($qb, $criteria);

        // limit
        $qb->setMaxResults($criteria->getLimit());
        $qb->setFirstResult($criteria->getOffset());

        //                dd($qb->getQuery()->getSQL());
        return $qb;
    }

    /**
     * @param mixed[] $filters
     */
    private function enrichQueryBuilderFilters(
        QueryBuilder $qb,
        array $filters,
        string $rootAlias = 'c',
    ): void {
        foreach ($this->buildFilters($qb, $filters, $rootAlias) as $expr) {
            $qb->andWhere($expr);
        }
    }

    /**
     * @param mixed[] $filters
     *
     * @return iterable<Expr\Base|string>
     */
    private function buildFilters(QueryBuilder $qb, array $filters, string $rootAlias = 'c', string $param_prefix = ''): iterable
    {
        $joins = [];
        foreach ($filters as $key => $value) {
            if ('or' === $key || 'and' === $key) {
                // groupement de filtres
                $expr = 'or' === $key ? $qb->expr()->orX() : $qb->expr()->andX();
                foreach ($value as $k => $oneFilter) {
                    foreach ($this->buildFilters($qb, $oneFilter, $rootAlias, $param_prefix.'_'.$key.$k.'_') as $subExpr) {
                        $expr->add($subExpr);
                    }
                }
                yield $expr;
            } elseif (str_contains($key, '.')) {
                // nested : relation.field
                [$relation, $field] = explode('.', $key, 2);

                // alias unique par relation
                if (!isset($joins[$relation])) {
                    $alias = $relation[0]; // t pour type, p pour project, etc.
                    $joins[$relation] = $alias;

                    $qb->join(sprintf('%s.%s', $rootAlias, $relation), $alias);
                } else {
                    $alias = $joins[$relation];
                }

                $param = $param_prefix.str_replace('.', '_', $key);

                yield $this->applyFilter($value, $qb, $alias, $field, $param);
            } else {
                // champ simple
                yield $this->applyFilter($value, $qb, $rootAlias, $key, $param_prefix.$key);
            }
        }
    }

    protected function enrichQueryBuilderOrder(QueryBuilder $qb, Criteria $criteria): void
    {
        $rootAlias = $qb->getRootAliases()[0] ?? '';
        // orderBy
        foreach ($criteria->getOrderBy() ?? [] as $sortKey => $sortDirection) {
            // add alias if needed
            if (str_starts_with($sortKey, $rootAlias.'.')) {
                $sortKey = substr($sortKey, strlen($rootAlias) + 1);
            }
            $qb->orderBy($rootAlias.'.'.$sortKey, $sortDirection);
        }
    }

    protected function applyFilter(mixed $value, QueryBuilder $qb, string $alias, string $field, string $param): Expr\Base|string
    {
        // json field support
        if (str_contains($field, ':')) {
            [$field, $jsonKey] = explode(':', $field, 2);
            $field = sprintf("JSON_VALUE(%s.%s, '$.%s')", $alias, $field, $jsonKey);
            // remove : for param name
            $param = str_replace(':', '_', $param);
        } else {
            $field = sprintf('%s.%s', $alias, $field);
        }

        if (is_array($value)) {
            if (isset($value['type'])) {
                // complex filter
                return $this->buildSingleFilterExpr($value, $field, $param, $qb, $alias);
            }

            // multiple value for simple IN
            $qb->setParameter($param, $value);

            return sprintf('%s IN (:%s)', $field, $param);
        }

        // single value equals
        $qb->setParameter($param, $value);

        return sprintf('%s = :%s', $field, $param);
    }

    /**
     * @param array<string,mixed> $filterData
     * @param string              $expectedType pipe separated expected types
     */
    private function extractValue(string $field, array $filterData, string $expectedType = '', string $valueField = 'value'): mixed
    {
        if (!isset($filterData[$valueField])) {
            throw new \LogicException(sprintf('%s : "%s" key is missing', $field, $valueField));
        }
        $filterData = $filterData[$valueField];
        $type = get_debug_type($filterData);
        if ('' !== $expectedType && !str_contains($expectedType, $type)) {
            throw new \LogicException(sprintf('%s : "%s" is not of type %s (got %s)', $field, $valueField, $expectedType, $type));
        }

        return $filterData;
    }

    /**
     * @param array<string,mixed> $filterData
     */
    private function buildSingleFilterExpr(array $filterData, string $field, string $parameterName, QueryBuilder $qb, string $alias): Expr\Base|string
    {
        $filterType = $filterData['type'] ?? throw new \LogicException($field.' : "type" key is missing');
        switch ($filterType) {
            case 'null':
                // direct return, no param needed
                return $qb->expr()->isNull($field);
            case 'not_null':
                // direct return, no param needed
                return $qb->expr()->isNotNull($field);
            case 'in':
            case 'equals_any':
                $expr = $qb->expr()->in($field, ':'.$parameterName);
                $paramValue = $this->extractValue($field, $filterData, 'array');
                break;
            case 'not_equals_any':
            case 'not_in':
                $expr = $qb->expr()->notIn($field, ':'.$parameterName);
                $paramValue = $this->extractValue($field, $filterData, 'array');
                break;
            case 'contains':
                $expr = $qb->expr()->like($field, ':'.$parameterName);
                $paramValue = $this->extractValue($field, $filterData, 'string');
                $paramValue = '%'.$paramValue.'%';
                break;
            case 'starts_with':
                $expr = $qb->expr()->like($field, ':'.$parameterName);
                $paramValue = $this->extractValue($field, $filterData, 'string').'%';
                break;
            case 'ends_with':
                $expr = $qb->expr()->like($field, ':'.$parameterName);
                $paramValue = '%'.$this->extractValue($field, $filterData, 'string');
                break;
            case 'greater_than':
                $expr = $qb->expr()->gt($field, ':'.$parameterName);
                $paramValue = $this->extractValue($field, $filterData, 'int|float');
                break;
            case 'less_than':
                $expr = $qb->expr()->lt($field, ':'.$parameterName);
                $paramValue = $this->extractValue($field, $filterData, 'int|float');
                break;
            case 'between':
                $expr = $qb->expr()->between($field, ':'.$parameterName.'_from', ':'.$parameterName.'_to');
                $qb->setParameter($parameterName.'_from', $this->extractValue($field, $filterData, 'int|float', 'from'));
                $qb->setParameter($parameterName.'_to', $this->extractValue($field, $filterData, 'int|float', 'to'));

                return $expr;
            case 'equals':
                $expr = $qb->expr()->eq($field, ':'.$parameterName);
                $paramValue = $this->extractValue($field, $filterData);
                break;
            case 'not_equals':
                $expr = $qb->expr()->neq($field, ':'.$parameterName);
                $paramValue = $this->extractValue($field, $filterData);
                break;
            case 'less_than_or_equal':
                $expr = $qb->expr()->lte($field, ':'.$parameterName);
                $paramValue = $this->extractValue($field, $filterData, 'int|float');
                break;
            case 'greater_than_or_equal':
                $expr = $qb->expr()->gte($field, ':'.$parameterName);
                $paramValue = $this->extractValue($field, $filterData, 'int|float');
                break;
            case 'and':
                $expr = $qb->expr()->andX();
                $subFilters = $this->extractValue($field, $filterData, 'array', 'filters');
                foreach ($subFilters as $k => $subFilter) {
                    $subParam = $parameterName.'_and'.$k;
                    $expr->add($this->buildSingleFilterExpr($subFilter, $field, $subParam, $qb, $alias));
                }

                return $expr;
            case 'or':
                $expr = $qb->expr()->orX();
                $subFilters = $this->extractValue($field, $filterData, 'array', 'filters');
                foreach ($subFilters as $k => $subFilter) {
                    $subParam = $parameterName.'_or'.$k;
                    $expr->add($this->buildSingleFilterExpr($subFilter, $field, $subParam, $qb, $alias));
                }

                return $expr;

            default:
                // trigger event to allow custom filter types
                $event = new CustomFilterEvent($filterType, $field, $filterData, $qb, $alias, $parameterName);
                $this->eventDispatcher->dispatch($event);

                return $event->getExpr()
                 ?? throw new \LogicException('unknown filter type '.$filterType);
        }

        $qb->setParameter($parameterName, $paramValue);

        return $expr;
    }
}
