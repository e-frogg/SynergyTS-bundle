<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Acl;

use Efrogg\Synergy\Entity\SynergyEntityInterface;
use Efrogg\Synergy\Event\AclClassGrantEvent;
use Efrogg\Synergy\Event\AclEntityGrantEvent;
use Efrogg\Synergy\Exception\GrantException;
use Efrogg\Synergy\Helper\EntityHelper;
use Psr\EventDispatcher\EventDispatcherInterface;

class AclManager
{
    public const string CREATE = 'create';
    public const string READ = 'read';
    public const string UPDATE = 'update';
    public const string DELETE = 'delete';
    public const array ACTION_LIST = [self::CREATE, self::READ, self::UPDATE, self::DELETE];

    private bool $defaultGrant = false;

    /**
     * @var array<string,bool>
     */
    private array $defaultActionGrants = [
        self::CREATE => false,
        self::READ => false,
        self::UPDATE => false,
        self::DELETE => false,
    ];

    /**
     * @var array<class-string<SynergyEntityInterface>,array<string,bool>>
     */
    private array $defaultEntityGrants = [];

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EntityHelper $entityHelper,
        private readonly AclContext $context,
    ) {
    }

    private bool $enabled = true;

    public function setDefaultGrant(bool $defaultGrant): void
    {
        $this->defaultGrant = $defaultGrant;
    }

    public function setDefaultActionGrant(string $action, bool $defaultGrant): void
    {
        $this->validateAction($action);
        $this->defaultActionGrants[$action] = $defaultGrant;
    }

    /**
     * @param array<string,bool> $defaultActionGrants
     */
    public function setDefaultActionGrants(array $defaultActionGrants): void
    {
        foreach ($defaultActionGrants as $action => $defaultGrant) {
            $this->setDefaultActionGrant($action, $defaultGrant);
        }
    }

    /**
     * @param array<class-string<SynergyEntityInterface>|string,array<string,bool>> $defaultEntityGrants
     */
    public function setDefaultEntityGrants(array $defaultEntityGrants): void
    {
        foreach ($defaultEntityGrants as $entityClass => $grants) {
            if (!is_a($entityClass, SynergyEntityInterface::class, true)) {
                // try to find
                $entityClass = $this->entityHelper->findEntityClass($entityClass);
            }
            $this->setEntityGrants($entityClass, $grants);
        }
    }

    /**
     * @param class-string<SynergyEntityInterface> $entityClass
     * @param array<string,bool>                   $grants
     */
    public function setEntityGrants(string $entityClass, array $grants): void
    {
        foreach ($grants as $action => $grant) {
            $this->validateAction($action);
            $this->defaultEntityGrants[$entityClass][$action] = $grant;
        }
    }

    /**
     * @param class-string<SynergyEntityInterface> $entityClass
     */
    public function setEntityGrant(string $entityClass, string $action, bool $defaultGrant): void
    {
        $this->validateAction($action);
        if (!is_a($entityClass, SynergyEntityInterface::class, true)) {
            throw new \InvalidArgumentException(sprintf('Invalid entity class. %s must implement %s', $entityClass, SynergyEntityInterface::class));
        }
        $this->defaultEntityGrants[$entityClass][$action] = $defaultGrant;
    }

    /**
     * @param class-string<SynergyEntityInterface> $entityClass
     *
     * @throws GrantException
     */
    public function checkClassIsGranted(string $entityClass, string $string): void
    {
        if (!$this->isEnabled() || $this->context->isSystemContext()) {
            return;
        }
        $this->isClassGranted($entityClass, $string, true);
    }

    /**
     * @throws GrantException
     */
    public function checkEntityIsGranted(SynergyEntityInterface $entity, string $action): void
    {
        if (!$this->isEnabled() || $this->context->isSystemContext()) {
            return;
        }
        $this->isEntityGranted($entity, $action, true);
    }

    /**
     * @throws GrantException
     */
    public function isEntityGranted(SynergyEntityInterface $entity, string $action, bool $throwException = false): bool
    {
        if (!$this->isEnabled() || $this->context->isSystemContext()) {
            return true;
        }
        try {
            $classGranted = $this->isClassGranted(get_class($entity), $action, true);
            $violations = [];
        } catch (GrantException $e) {
            $violations = $e->getViolations();
            $classGranted = false;
        }

        $event = new AclEntityGrantEvent($entity, $action, $classGranted, $violations);

        // dispatch event
        $this->eventDispatcher->dispatch($event);

        if ($throwException && !$event->isGranted()) {
            throw new GrantException(sprintf('Entity-level access denied (%s %s [%s])', $action, $entity::getEntityName(), $entity->getId()), $event->getCode(), $event->getViolations());
        }

        return $event->isGranted();
    }

    public function isClassGranted(string $entityClass, string $action, bool $throwException = false): bool
    {
        if (!$this->isEnabled() || $this->context->isSystemContext()) {
            return true;
        }
        $defaultGrant =
            $this->defaultEntityGrants[$entityClass][$action]
            ?? $this->defaultActionGrants[$action]
            ?? $this->defaultGrant;

        $violations = [];
        if (!$defaultGrant) {
            $violations[] = sprintf('class-level access denied (%s %s)', $action, $entityClass);
        }

        $event = new AclClassGrantEvent($entityClass, $action, $defaultGrant, $violations);

        // dispatch event
        $this->eventDispatcher->dispatch($event);

        if ($throwException && !$event->isGranted()) {
            throw new GrantException(sprintf('class-level access denied (%s %s)', $action, $entityClass), $event->getCode(), $event->getViolations());
        }

        return $event->isGranted();
    }

    private function validateAction(string $action): void
    {
        if (!in_array($action, self::ACTION_LIST, true)) {
            throw new \InvalidArgumentException('Invalid action');
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}
