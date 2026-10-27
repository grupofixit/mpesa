<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Exceptions;

use GrupoFixIT\Mpesa\Enums\ResponseCode;

class ApiException extends MpesaException
{
    /**
     * @param int $httpStatus
     * @param ResponseCode $responseCode
     * @param string|null $rawCode
     * @param string|null $responseDesc
     * @param array<string, mixed> $rawResponse
     */
    public function __construct(
        private readonly int $httpStatus,
        private readonly ResponseCode $responseCode,
        private readonly ?string $rawCode = null,
        private readonly ?string $responseDesc = null,
        private readonly array $rawResponse = [],
        ?\Throwable $previous = null,
    ) {
        $message = sprintf(
            'Erro na API M-Pesa (HTTP %d, Código: %s): %s',
            $this->httpStatus,
            $this->rawCode ?? $this->responseCode->value,
            $this->responseDesc ?? $this->responseCode->descriptionPt()
        );

        parent::__construct($message, $this->httpStatus, $previous);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getResponseCode(): ResponseCode
    {
        return $this->responseCode;
    }

    public function getRawCode(): ?string
    {
        return $this->rawCode;
    }

    public function getResponseDesc(): ?string
    {
        return $this->responseDesc;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRawResponse(): array
    {
        return $this->rawResponse;
    }
}
