<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Auth;

use GrupoFixIT\Mpesa\Config\Credentials;
use Psr\SimpleCache\CacheInterface;

final class CachedTokenProvider implements TokenProviderInterface
{
    private ?string $memoryToken = null;
    private string $cacheKey;

    public function __construct(
        private readonly Credentials $credentials,
        private readonly ?CacheInterface $cache = null,
        private readonly int $ttl = 3300,
    ) {
        $this->cacheKey = sprintf('mpesa_token_%s', hash('sha256', $this->credentials->apiKey . $this->credentials->publicKey));
    }

    public function getToken(bool $forceRefresh = false): string
    {
        if (!$forceRefresh && $this->memoryToken !== null) {
            return $this->memoryToken;
        }

        if (!$forceRefresh && $this->cache !== null) {
            try {
                $cached = $this->cache->get($this->cacheKey);
                if (is_string($cached) && $cached !== '') {
                    $this->memoryToken = $cached;
                    return $cached;
                }
            } catch (\Throwable) {
                // Se falhar o cache, continua para geração
            }
        }

        $freshToken = TokenGenerator::generate($this->credentials);
        $this->memoryToken = $freshToken;

        if ($this->cache !== null) {
            try {
                $this->cache->set($this->cacheKey, $freshToken, $this->ttl);
            } catch (\Throwable) {
                // Silencia falhas transitórias de escrita em cache
            }
        }

        return $freshToken;
    }

    public function invalidateToken(): void
    {
        $this->memoryToken = null;

        if ($this->cache !== null) {
            try {
                $this->cache->delete($this->cacheKey);
            } catch (\Throwable) {
                // Silencia falhas de remoção
            }
        }
    }
}
