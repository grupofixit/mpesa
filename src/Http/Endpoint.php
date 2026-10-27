<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Http;

use GrupoFixIT\Mpesa\Config\EnvironmentRegistry;
use GrupoFixIT\Mpesa\Enums\Environment;
use GrupoFixIT\Mpesa\Enums\Operation;

final readonly class Endpoint
{
    public function __construct(
        public Operation $operation,
        public Environment $environment,
        public ?string $customBaseUrl = null,
    ) {
    }

    public function url(): string
    {
        return EnvironmentRegistry::buildUrl($this->environment, $this->operation, $this->customBaseUrl);
    }

    public function method(): string
    {
        return EnvironmentRegistry::getHttpMethod($this->operation);
    }

    public function port(): int
    {
        return EnvironmentRegistry::getPort($this->operation);
    }

    public function path(): string
    {
        return EnvironmentRegistry::getPath($this->operation);
    }
}
