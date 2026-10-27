<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Callback;

final readonly class CallbackAcknowledgement
{
    public const DEFAULT_SUCCESS_DESC = 'Successfully Accepted Result';
    public const DEFAULT_SUCCESS_CODE = '0';

    public function __construct(
        public string $originalConversationId,
        public string $thirdPartyConversationId,
        public string $responseDesc = self::DEFAULT_SUCCESS_DESC,
        public string $responseCode = self::DEFAULT_SUCCESS_CODE,
    ) {
    }

    public static function forPayload(CallbackPayload $payload): self
    {
        return new self(
            originalConversationId: $payload->originalConversationId,
            thirdPartyConversationId: $payload->thirdPartyReference,
        );
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'output_OriginalConversationID' => $this->originalConversationId,
            'output_ResponseDesc' => $this->responseDesc,
            'output_ResponseCode' => $this->responseCode,
            'output_ThirdPartyConversationID' => $this->thirdPartyConversationId,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
