<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Requests;

use GrupoFixIT\Mpesa\Support\Money;
use GrupoFixIT\Mpesa\Support\Msisdn;
use GrupoFixIT\Mpesa\Support\Reference;

final readonly class B2CRequest
{
    public function __construct(
        public Msisdn $customerMsisdn,
        public Money $amount,
        public string $transactionReference,
        public string $thirdPartyReference,
        public ?string $serviceProviderCode = null,
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
            'input_CustomerMSISDN' => $this->customerMsisdn->getValue(),
            'input_Amount' => $this->amount->getAmount(),
            'input_ThirdPartyReference' => $this->thirdPartyReference,
            'input_ServiceProviderCode' => $this->serviceProviderCode ?? $defaultServiceProviderCode,
        ];
    }
}
