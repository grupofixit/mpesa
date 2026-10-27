<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Callback;

use GrupoFixIT\Mpesa\Enums\ResponseCode;
use GrupoFixIT\Mpesa\Support\Redactor;

final readonly class CallbackPayload
{
    /**
     * @param string $originalConversationId
     * @param string $thirdPartyReference
     * @param string|null $transactionId
     * @param string $resultCode
     * @param string $resultDescription
     * @param ResponseCode $responseCode
     * @param array<string, mixed> $rawPayload
     */
    public function __construct(
        public string $originalConversationId,
        public string $thirdPartyReference,
        public ?string $transactionId,
        public string $resultCode,
        public string $resultDescription,
        public ResponseCode $responseCode,
        public array $rawPayload,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->resultCode === '0' || $this->resultCode === 'INS-0' || $this->responseCode->isSuccess();
    }

    /**
     * @return array<int|string, mixed>
     */
    public function toArray(): array
    {
        return Redactor::redactArray([
            'original_conversation_id' => $this->originalConversationId,
            'third_party_reference' => $this->thirdPartyReference,
            'transaction_id' => $this->transactionId,
            'result_code' => $this->resultCode,
            'result_description' => $this->resultDescription,
            'response_code' => $this->responseCode->value,
            'raw' => $this->rawPayload,
        ]);
    }
}
