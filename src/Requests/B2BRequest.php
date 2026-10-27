<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Requests;

use GrupoFixIT\Mpesa\Support\Money;
use GrupoFixIT\Mpesa\Support\Reference;

final readonly class B2BRequest
{
    public function __construct(
        public string $transactionReference,
        public Money $amount,
        public string $thirdPartyReference,
        public string $receiverPartyCode,
        public ?string $primaryPartyCode = null,
    ) {
        Reference::validateTransactionReference($this->transactionReference);
        Reference::validateThirdPartyReference($this->thirdPartyReference);
    }

    /**
     * @return array<string, string>
     */
    public function toPayload(string $defaultServiceProviderCode): array
    {
        return [
            'input_TransactionReference' => $this->transactionReference,
            'input_Amount' => $this->amount->getAmount(),
            'input_ThirdPartyReference' => $this->thirdPartyReference,
            'input_PrimaryPartyCode' => $this->primaryPartyCode ?? $defaultServiceProviderCode,
            'input_ReceiverPartyCode' => $this->receiverPartyCode,
        ];
    }
}
