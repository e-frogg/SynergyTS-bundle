<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Event;

use Symfony\Component\HttpFoundation\Response;

abstract class AclGrantEvent
{

    public const int FORBIDDEN = Response::HTTP_FORBIDDEN;
    public const int UNAUTHORIZED = Response::HTTP_UNAUTHORIZED;

    private int $code = 0;

    /**
     * @param array<string> $violations
     */
    public function __construct(
        private readonly string $action,
        private bool $granted,
        private array $violations = [],
    ) {
    }


    public function getAction(): string
    {
        return $this->action;
    }

    public function isGranted(): bool
    {
        return $this->granted;
    }

    public function setGranted(bool $granted): bool
    {
        $this->granted = $granted;
        return $granted;
    }

    public function addViolation(string $violation, int $violationCode = self::UNAUTHORIZED): bool
    {
        $this->granted = false;
        $this->violations[] = $violation;
        $this->setCode(max($this->code,$violationCode));
        return false;
    }

    /**
     * @param int $code
     */
    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return array<string>
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

}
