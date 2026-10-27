<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Callback;

use GrupoFixIT\Mpesa\Enums\ResponseCode;
use GrupoFixIT\Mpesa\Exceptions\ValidationException;

final readonly class CallbackParser
{
    /**
     * @param string|array<string, mixed> $input
     * @throws ValidationException
     */
    public static function parse(string|array $input): CallbackPayload
    {
        if (is_string($input)) {
            try {
                $decoded = json_decode(trim($input), true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($decoded)) {
                    throw new ValidationException('O payload do callback deve ser um objeto JSON válido.');
                }
                /** @var array<string, mixed> $data */
                $data = $decoded;
            } catch (\JsonException $e) {
                throw new ValidationException('JSON inválido no callback da Vodacom: ' . $e->getMessage(), [], 0, $e);
            }
        } else {
            $data = $input;
        }

        $conversationId = isset($data['input_OriginalConversationID']) && is_scalar($data['input_OriginalConversationID']) ? (string) $data['input_OriginalConversationID'] : '';
        $thirdPartyRef = isset($data['input_ThirdPartyReference']) && is_scalar($data['input_ThirdPartyReference']) ? (string) $data['input_ThirdPartyReference'] : '';
        $transactionId = isset($data['input_TransactionID']) && is_scalar($data['input_TransactionID']) ? (string) $data['input_TransactionID'] : null;
        $resultCode = isset($data['input_ResultCode']) && is_scalar($data['input_ResultCode']) ? (string) $data['input_ResultCode'] : '';
        $resultDesc = isset($data['input_ResultDesc']) && is_scalar($data['input_ResultDesc']) ? (string) $data['input_ResultDesc'] : '';

        if ($conversationId === '') {
            throw new ValidationException('Callback inválido: input_OriginalConversationID em falta.');
        }

        if ($thirdPartyRef === '') {
            throw new ValidationException('Callback inválido: input_ThirdPartyReference em falta.');
        }

        $responseCode = ResponseCode::fromRaw($resultCode);

        return new CallbackPayload(
            originalConversationId: $conversationId,
            thirdPartyReference: $thirdPartyRef,
            transactionId: $transactionId,
            resultCode: $resultCode,
            resultDescription: $resultDesc,
            responseCode: $responseCode,
            rawPayload: $data
        );
    }
}
