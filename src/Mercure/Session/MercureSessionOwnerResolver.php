<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Mercure\Session;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class MercureSessionOwnerResolver
{
    public function __construct(private Security $security)
    {
    }

    public function resolveOwnerId(): string
    {
        $user = $this->security->getUser();
        if (!$user instanceof UserInterface) {
            throw new AccessDeniedHttpException('Authentication required.');
        }

        return $user->getUserIdentifier();
    }
}
