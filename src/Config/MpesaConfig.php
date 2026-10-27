<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Config;

use GrupoFixIT\Mpesa\Enums\Environment;
use GrupoFixIT\Mpesa\Exceptions\ConfigurationException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

final readonly class MpesaConfig
{
    public const DEFAULT_CONNECT_TIMEOUT = 10;
    public const DEFAULT_TOTAL_TIMEOUT = 60;
    public const DEFAULT_TOKEN_TTL = 3300; // 55 minutos

    public function __construct(
        public Environment $environment,
        public Credentials $credentials,
        public ?ClientInterface $httpClient = null,
        public ?RequestFactoryInterface $requestFactory = null,
        public ?StreamFactoryInterface $streamFactory = null,
        public ?LoggerInterface $logger = null,
        public ?CacheInterface $cache = null,
        public int $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT,
        public int $timeout = self::DEFAULT_TOTAL_TIMEOUT,
        public int $tokenTtl = self::DEFAULT_TOKEN_TTL,
        public ?string $customBaseUrl = null,
        public string $origin = EnvironmentRegistry::DEFAULT_ORIGIN,
        public ?string $caBundle = null,
    ) {
        if ($this->connectTimeout <= 0) {
            throw new ConfigurationException('connectTimeout deve ser superior a 0 segundos.');
        }

        if ($this->timeout <= 0) {
            throw new ConfigurationException('timeout total deve ser superior a 0 segundos.');
        }

        if ($this->tokenTtl <= 0) {
            throw new ConfigurationException('tokenTtl deve ser superior a 0 segundos.');
        }

        if ($this->customBaseUrl !== null) {
            $parsed = parse_url($this->customBaseUrl);
            if (!is_array($parsed) || empty($parsed['scheme']) || empty($parsed['host'])) {
                throw new ConfigurationException(sprintf(
                    'customBaseUrl inválido: "%s". Deve ser uma URL válida com scheme (https/http) e host.',
                    $this->customBaseUrl
                ));
            }
        }
    }
}
