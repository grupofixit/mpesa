<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Requests;

use GrupoFixIT\Mpesa\Support\Msisdn;
use GrupoFixIT\Mpesa\Support\Reference;

final readonly class CustomerNameRequest
{
    public function __construct(
        public Msisdn $customerMsisdn,
        public string $thirdPartyReference,
        public ?string $serviceProviderCode = null,
    ) {
        Reference::validateThirdPartyReference($this->thirdPartyReference);
    }

    /**
     * @return array<string, string>
     */
    public function toPayload(string $defaultServiceProviderCode): array
    {
        return [
            'input_CustomerMSISDN' => $this->customerMsisdn->getValue(),
            'input_ThirdPartyReference' => $this->thirdPartyReference,
            'input_ServiceProviderCode' => $this->serviceProviderCode ?? $defaultServiceProviderCode,
        ];
    }
}
