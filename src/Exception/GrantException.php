<?php

declare(strict_types=1);

namespace Efrogg\Synergy\Exception;

use Exception;
use Throwable;

class GrantException extends Exception
{
    /**
     * @param string         $message
     * @param int            $code
     * @param array<string>  $violations
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message = "",
        int $code = 0,
        private readonly array $violations = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string>
     */
    public function getViolations(): array
    {
        return $this->violations;
    }
}
