<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Exceptions;

class ValidationException extends MpesaException
{
    /**
     * @param string $message
     * @param array<string, string> $errors
     */
    public function __construct(
        string $message,
        private readonly array $errors = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
