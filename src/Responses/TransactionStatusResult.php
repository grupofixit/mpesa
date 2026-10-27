<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Responses;

use GrupoFixIT\Mpesa\Enums\ResponseCode;
use GrupoFixIT\Mpesa\Enums\TransactionStatus;
use Psr\Http\Message\ResponseInterface;

final readonly class TransactionStatusResult extends MpesaResponse
{
    public function __construct(
        int $httpStatus,
        ResponseCode $responseCode,
        string $rawResponseCode,
        string $responseDescription,
        ?string $conversationId,
        ?string $thirdPartyReference,
        ?string $transactionId,
        public TransactionStatus $transactionStatus,
        public string $rawTransactionStatus,
        array $rawArray,
    ) {
        parent::__construct(
            httpStatus: $httpStatus,
            responseCode: $responseCode,
            rawResponseCode: $rawResponseCode,
            responseDescription: $responseDescription,
            conversationId: $conversationId,
            thirdPartyReference: $thirdPartyReference,
            transactionId: $transactionId,
            rawArray: $rawArray
        );
    }

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

        /** @var string $rawTxStatus */
        $rawTxStatus = $data['output_ResponseTransactionStatus'] ?? '';
        $txStatus = TransactionStatus::fromRaw($rawTxStatus);

        return new self(
            httpStatus: $status,
            responseCode: $responseCode,
            rawResponseCode: $rawCode,
            responseDescription: $responseDesc,
            conversationId: $conversationId,
            thirdPartyReference: $thirdPartyRef,
            transactionId: $transactionId,
            transactionStatus: $txStatus,
            rawTransactionStatus: $rawTxStatus,
            rawArray: $data
        );
    }
}
