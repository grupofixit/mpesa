<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Responses;

use GrupoFixIT\Mpesa\Enums\ResponseCode;
use GrupoFixIT\Mpesa\Exceptions\ApiException;
use GrupoFixIT\Mpesa\Support\Redactor;
use Psr\Http\Message\ResponseInterface;

abstract readonly class MpesaResponse
{
    /**
     * @param int $httpStatus
     * @param ResponseCode $responseCode
     * @param string $rawResponseCode
     * @param string $responseDescription
     * @param string|null $conversationId
     * @param string|null $thirdPartyReference
     * @param string|null $transactionId
     * @param array<string, mixed> $rawArray
     */
    public function __construct(
        public int $httpStatus,
        public ResponseCode $responseCode,
        public string $rawResponseCode,
        public string $responseDescription,
        public ?string $conversationId,
        public ?string $thirdPartyReference,
        public ?string $transactionId,
        public array $rawArray,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->responseCode->isSuccess();
    }

    /**
     * @return array<int|string, mixed>
     */
    public function toArray(): array
    {
        return Redactor::redactArray([
            'http_status' => $this->httpStatus,
            'response_code' => $this->responseCode->value,
            'raw_response_code' => $this->rawResponseCode,
            'response_description' => $this->responseDescription,
            'conversation_id' => $this->conversationId,
            'third_party_reference' => $this->thirdPartyReference,
            'transaction_id' => $this->transactionId,
            'raw' => $this->rawArray,
        ]);
    }

    /**
     * Extrai e valida com segurança o corpo de uma resposta HTTP da Vodacom.
     * Nunca lança TypeError mesmo se o corpo for HTML, string vazia ou JSON corrompido.
     *
     * @param ResponseInterface $response
     * @return array<string, mixed>
     * @throws ApiException se o corpo não for JSON válido e o status for de erro
     */
    public static function parseResponseBody(ResponseInterface $response): array
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $trimmed = trim($body);

        if ($trimmed === '') {
            if ($status >= 400) {
                throw new ApiException(
                    httpStatus: $status,
                    responseCode: ResponseCode::Unknown,
                    rawCode: null,
                    responseDesc: 'Resposta vazia recebida do servidor M-Pesa.',
                    rawResponse: []
                );
            }
            return [];
        }

        try {
            $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decoded)) {
                /** @var array<string, mixed> $decoded */
                return $decoded;
            }
        } catch (\JsonException $e) {
            // Corpo recebido não é JSON (ex.: página de erro HTML 502/504)
            throw new ApiException(
                httpStatus: $status,
                responseCode: ResponseCode::Unknown,
                rawCode: null,
                responseDesc: sprintf('Resposta não-JSON recebida da Vodacom (HTTP %d): %s', $status, substr($trimmed, 0, 100)),
                rawResponse: ['raw_body' => substr($trimmed, 0, 500)],
                previous: $e
            );
        }

        return [];
    }

    /**
     * Extrai o código de resposta seguro.
     *
     * @param array<string, mixed> $data
     */
    protected static function extractCode(array $data): string
    {
        if (isset($data['output_ResponseCode']) && is_string($data['output_ResponseCode'])) {
            return $data['output_ResponseCode'];
        }

        if (isset($data['input_ResultCode']) && (is_string($data['input_ResultCode']) || is_numeric($data['input_ResultCode']))) {
            return (string) $data['input_ResultCode'];
        }

        return ResponseCode::Unknown->value;
    }

    /**
     * Extrai a descrição de resposta.
     *
     * @param array<string, mixed> $data
     */
    protected static function extractDescription(array $data): string
    {
        if (isset($data['output_ResponseDesc']) && is_string($data['output_ResponseDesc'])) {
            return $data['output_ResponseDesc'];
        }

        if (isset($data['input_ResultDesc']) && is_string($data['input_ResultDesc'])) {
            return $data['input_ResultDesc'];
        }

        return '';
    }
}
