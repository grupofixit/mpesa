<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Responses;

use GrupoFixIT\Mpesa\Enums\ResponseCode;
use Psr\Http\Message\ResponseInterface;

final readonly class TransactionResult extends MpesaResponse
{
    /**
     * Converte uma resposta HTTP PSR-7 em TransactionResult.
     */
    public static function fromPsrResponse(ResponseInterface $response): self
    {
        $status = $response->getStatusCode();
        $data = self::parseResponseBody($response);

        $rawCode = self::extractCode($data);
        $responseCode = ResponseCode::fromRaw($rawCode);
        $responseDesc = self::extractDescription($data);

        /** @var string|null $conversationId */
        $conversationId = $data['output_ConversationID'] ?? null;
        /** @var string|null $thirdPartyRef */
        $thirdPartyRef = $data['output_ThirdPartyReference'] ?? null;
        /** @var string|null $transactionId */
        $transactionId = $data['output_TransactionID'] ?? null;

        return new self(
            httpStatus: $status,
            responseCode: $responseCode,
            rawResponseCode: $rawCode,
            responseDescription: $responseDesc,
            conversationId: $conversationId,
            thirdPartyReference: $thirdPartyRef,
            transactionId: $transactionId,
            rawArray: $data
        );
    }
}
