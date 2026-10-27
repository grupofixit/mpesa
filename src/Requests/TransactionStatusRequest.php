<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Requests;

use GrupoFixIT\Mpesa\Support\Reference;

final readonly class TransactionStatusRequest
{
    public function __construct(
        public string $thirdPartyReference,
        public string $queryReference,
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
            'input_ThirdPartyReference' => $this->thirdPartyReference,
            'input_QueryReference' => $this->queryReference,
            'input_ServiceProviderCode' => $this->serviceProviderCode ?? $defaultServiceProviderCode,
        ];
    }
}
