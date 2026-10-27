<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Requests;

use GrupoFixIT\Mpesa\Config\Credentials;
use GrupoFixIT\Mpesa\Exceptions\ValidationException;
use GrupoFixIT\Mpesa\Support\Money;
use GrupoFixIT\Mpesa\Support\Reference;

final readonly class ReversalRequest
{
    public function __construct(
        public string $transactionId,
        public string $thirdPartyReference,
        public ?Money $reversalAmount = null,
        public ?string $securityCredential = null,
        public ?string $initiatorIdentifier = null,
        public ?string $serviceProviderCode = null,
    ) {
        if (trim($this->transactionId) === '') {
            throw new ValidationException('TransactionID não pode ser vazio para estorno.');
        }

        Reference::validateThirdPartyReference($this->thirdPartyReference);
    }

    /**
     * @return array<string, string>
     */
    public function toPayload(Credentials $credentials): array
    {
        $secCred = $this->securityCredential ?? $credentials->securityCredential;
        $initiator = $this->initiatorIdentifier ?? $credentials->initiatorIdentifier;
        $spCode = $this->serviceProviderCode ?? $credentials->serviceProviderCode;

        if ($secCred === null || $secCred === '') {
            throw new ValidationException('SecurityCredential é obrigatória para executar estornos.');
        }

        if ($initiator === null || $initiator === '') {
            throw new ValidationException('InitiatorIdentifier é obrigatório para executar estornos.');
        }

        $payload = [
            'input_TransactionID' => $this->transactionId,
            'input_SecurityCredential' => $secCred,
            'input_InitiatorIdentifier' => $initiator,
            'input_ThirdPartyReference' => $this->thirdPartyReference,
            'input_ServiceProviderCode' => $spCode,
        ];

        if ($this->reversalAmount !== null) {
            $payload['input_ReversalAmount'] = $this->reversalAmount->getAmount();
        }

        return $payload;
    }
}
