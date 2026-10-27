<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Http;

use GrupoFixIT\Mpesa\Enums\Operation;

final readonly class RetryPolicy
{
    public function __construct(
        private int $maxReadOnlyRetries = 2,
        private int $initialDelayMs = 200,
    ) {
    }

    /**
     * Determina se a operação e o erro qualificam para retry automático.
     * Operações financeiras NUNCA sofrem retry automático.
     */
    public function canRetry(Operation $operation, int $attempt, int $httpStatus = 0): bool
    {
        if ($operation->isMoneyMovement()) {
            return false;
        }

        if ($attempt >= $this->maxReadOnlyRetries) {
            return false;
        }

        // Retenta se for erro de rede (status 0) ou erro de servidor 5xx
        return $httpStatus === 0 || ($httpStatus >= 500 && $httpStatus < 600);
    }

    public function getDelayMs(int $attempt): int
    {
        return $this->initialDelayMs * (2 ** $attempt);
    }
}
