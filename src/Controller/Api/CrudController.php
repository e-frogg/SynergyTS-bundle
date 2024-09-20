<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Efrogg\Synergy\AutoSync\autoSyncService;
use Efrogg\Synergy\Context;
use Efrogg\Synergy\Controller\Trait\JsonRequestTrait;
use Efrogg\Synergy\Data\CriteriaParser;
use Efrogg\Synergy\Data\EntityRepositoryHelper;
use Efrogg\Synergy\Data\EntityResponseBuilder;
use Efrogg\Synergy\Entity\SynergyEnricher;
use Efrogg\Synergy\Entity\SynergyEntityInterface;
use Efrogg\Synergy\Exception\SerializerException;
use Efrogg\Synergy\Helper\EntityHelper;
use Efrogg\Synergy\Mercure\EntityUpdater;
use Exception;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

#[Route('/entity')]
class CrudController extends AbstractController
{
    use JsonRequestTrait;


    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EntityHelper $entityHelper,
        private readonly SynergyEnricher $SynergyEnricher,
        private readonly EntityRepositoryHelper $entityRepositoryHelper,
        private readonly CriteriaParser $criteriaParser,
        private readonly EntityResponseBuilder $entityResponseBuilder,
        private readonly autoSyncService $autoSyncService
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/full', name: 'Synergy_full', priority: 2)]
    public function full(Request $request): Response
    {
        $this->configureDiscover($request);

        $entities = [];
        foreach ($this->entityHelper->getEntityClasses() as $class) {
            if(!is_a($class, SynergyEntityInterface::class, true)) {
                throw new Exception(sprintf('Class %s is not an SynergyEntityInterface', $class));
            }
            $repo = $this->entityManager->getRepository($class);
            $entities = [...$entities, ...$repo->findAll()];
        }

        // add mercure topic
        $versionTopic = EntityUpdater::getFullUpdateTopic(Context::createDefaultContext()) ;

        return $this->entityResponseBuilder->buildResponse($entities, null, $versionTopic);

    }


    /**
     * @throws ExceptionInterface
     */
    #[Route('/{entityName}/{id}', name: 'Synergy__entity_get', methods: ['GET'], priority: 2)]
    public function getOne(Request $request,string $id, string $entityName): JsonResponse
    {
        $this->configureDiscover($request);
        $repo = $this->getEntityRepository($entityName);
        $entity = $repo->find($id);
        if(null === $entity) {
            return new JsonResponse(['error' => 'Entity not found'], 404);
        }

        return $this->entityResponseBuilder->buildResponse([$entity]);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/{entityName}', name: 'Synergy__entity_get_all', methods: ['GET'], priority: 2)]
    public function getAll(Request $request, string $entityName): JsonResponse
    {
        $this->configureDiscover($request);
        $repo = $this->getEntityRepository($entityName);
        $entities = $repo->findAll();

        return $this->entityResponseBuilder->buildResponse($entities);
    }

    #[Route('/search/{entityName}', name: 'Synergy__entity_search', methods: ['POST'], priority: 2)]
    public function search(Request $request, string $entityName): JsonResponse
    {
        $this->configureDiscover($request);
        $entityClass = $this->entityHelper->findEntityClass($entityName)
            ?? throw new NotFoundHttpException(sprintf('Entity [%s] not found', $entityName));

        $body = $this->extractJson($request);
        $criteria = $this->criteriaParser->parse($body);

        $result = $this->entityRepositoryHelper->search($entityClass, $criteria);
        if($criteria->isAutoSync()) {
            $autoSync = $this->autoSyncService->initAutoSync($entityClass, $criteria, $result);
            $topics = $autoSync->getTopic();
        }
        return $this->entityResponseBuilder->buildResponse($result->getEntities(), mainIds: $result->getMainIds());

    }


    /**
     * @throws SerializerException
     * @throws JsonException
     */
    #[Route('/{entityName}', name: 'Synergy__entity_create', methods: ['POST'], priority: 2)]
    public function create(Request $request, string $entityName): JsonResponse
    {
        $entityClass = $this->entityHelper->findEntityClass($entityName)
            ?? throw new NotFoundHttpException(sprintf('Entity [%s] not found', $entityName));

        return $this->createOrEdit($request, $entityClass, 'create');
    }

    /**
     * @throws SerializerException
     * @throws JsonException
     */
    #[Route('/{entityName}/{id}', name: 'Synergy__entity_edit', methods: ['PUT'], priority: 2)]
    public function edit(Request $request, string $entityName, string $id): JsonResponse
    {
        $entityClass = $this->entityHelper->findEntityClass($entityName)
            ?? throw new NotFoundHttpException(sprintf('Entity [%s] not found', $entityName));

        $entityRepository = $this->getEntityRepository($entityName);

        $entity = $entityRepository->find($id);
        if (null === $entity) {
            return new JsonResponse(['error' => 'Entity not found'], 404);
        }
        return $this->createOrEdit($request, $entityClass, 'edit', $entity);
    }

    /**
     * @param class-string<SynergyEntityInterface> $entityClass
     *
     * @throws JsonException
     * @throws SerializerException
     */
    private function createOrEdit(Request $request, string $entityClass, string $action, ?SynergyEntityInterface $entity = null): JsonResponse
    {

        $body = $this->extractJson($request);

        if($action === 'edit' && $body->has('id') && $body->get('id') !== $entity?->getId()) {
            return new JsonResponse(['error' => 'Not allowed to change entity Id'], 400);
        }
        $entity = $this->SynergyEnricher->createOrEdit($entityClass, $body, $entity);

        // ACL will be executed on preUpdate event
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        $responseData = [
            'id' => $entity->getId(),
        ];
        $responseCode = $action === 'create' ? Response::HTTP_CREATED : Response::HTTP_OK;
        return new JsonResponse($responseData, $responseCode);
    }

    #[Route('/{entityName}/{id}', name: 'Synergy__entity_delete', methods: ['DELETE'], priority: 2)]
    public function delete(string $entityName, string $id): JsonResponse
    {
        $entityRepository = $this->getEntityRepository($entityName);

        $entity = $entityRepository->find($id);
        if (null === $entity) {
            return new JsonResponse(['error' => 'Entity not found'], 404);
        }
        $this->entityManager->remove($entity);
        $this->entityManager->flush();

        return new JsonResponse(['id' => $id], Response::HTTP_NO_CONTENT);
    }

    /**
     * @param string $entityName
     *
     * @return EntityRepository<SynergyEntityInterface>
     */
    private function getEntityRepository(string $entityName): EntityRepository
    {
        /** @var class-string<SynergyEntityInterface> $entityClass */
        $entityClass = $this->entityHelper->findEntityClass($entityName);

        /** @var EntityRepository<SynergyEntityInterface> $entityRepository */
        $entityRepository = $this->entityManager->getRepository($entityClass);
        return $entityRepository;
    }

    private function configureDiscover(Request $request): void
    {
        $this->entityResponseBuilder->setDiscoverLevel($request->query->getInt('discover'));
    }

}
