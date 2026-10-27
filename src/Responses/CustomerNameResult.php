<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Responses;

use GrupoFixIT\Mpesa\Enums\ResponseCode;
use Psr\Http\Message\ResponseInterface;

final readonly class CustomerNameResult extends MpesaResponse
{
    public function __construct(
        int $httpStatus,
        ResponseCode $responseCode,
        string $rawResponseCode,
        string $responseDescription,
        ?string $conversationId,
        ?string $thirdPartyReference,
        ?string $transactionId,
        public string $customerName,
        public ?string $firstNameMasked,
        public ?string $secondNameMasked,
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

        /** @var string $name */
        $name = $data['output_CustomerName'] ?? '';

        $firstName = null;
        $secondName = null;
        if ($name !== '') {
            $parts = explode(' ', trim($name));
            $firstName = $parts[0];
            $secondName = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : null;
        }

        return new self(
            httpStatus: $status,
            responseCode: $responseCode,
            rawResponseCode: $rawCode,
            responseDescription: $responseDesc,
            conversationId: $conversationId,
            thirdPartyReference: $thirdPartyRef,
            transactionId: $transactionId,
            customerName: $name,
            firstNameMasked: $firstName,
            secondNameMasked: $secondName,
            rawArray: $data
        );
    }
}
