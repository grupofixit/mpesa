<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Config;

use GrupoFixIT\Mpesa\Exceptions\ConfigurationException;

final readonly class Credentials
{
    public string $apiKey;
    public string $publicKey;
    public string $serviceProviderCode;
    public ?string $initiatorIdentifier;
    public ?string $securityCredential;

    public function __construct(
        string $apiKey,
        string $publicKey,
        string $serviceProviderCode,
        ?string $initiatorIdentifier = null,
        ?string $securityCredential = null,
    ) {
        $cleanApiKey = trim($apiKey);
        $cleanPublicKey = trim($publicKey);
        $cleanServiceProviderCode = trim($serviceProviderCode);

        if ($cleanApiKey === '') {
            throw new ConfigurationException('A apiKey não pode ser vazia.');
        }

        if ($cleanPublicKey === '') {
            throw new ConfigurationException('A publicKey não pode ser vazia.');
        }

        if ($cleanServiceProviderCode === '') {
            throw new ConfigurationException('O serviceProviderCode não pode ser vazio.');
        }

        if (!ctype_digit($cleanServiceProviderCode)) {
            throw new ConfigurationException(sprintf(
                'O serviceProviderCode deve ser numérico (recebido: "%s").',
                $serviceProviderCode
            ));
        }

        $this->apiKey = $cleanApiKey;
        $this->publicKey = $cleanPublicKey;
        $this->serviceProviderCode = $cleanServiceProviderCode;
        $this->initiatorIdentifier = $initiatorIdentifier !== null ? trim($initiatorIdentifier) : null;
        $this->securityCredential = $securityCredential !== null ? trim($securityCredential) : null;
    }

    /**
     * Retorna a chave pública formatada no padrão PEM (com delimitadores e quebras de linha).
     */
    public function getFormattedPublicKey(): string
    {
        if (str_contains($this->publicKey, '-----BEGIN')) {
            return $this->publicKey;
        }

        // Remove espaços ou quebras existentes na chave crua
        $clean = preg_replace('/\s+/', '', $this->publicKey) ?? $this->publicKey;
        $wrapped = wordwrap($clean, 64, "\n", true);

        return "-----BEGIN PUBLIC KEY-----\n" . $wrapped . "\n-----END PUBLIC KEY-----\n";
    }

    /**
     * Verifica se as credenciais para estorno (Reversal) estão preenchidas.
     */
    public function hasReversalCredentials(): bool
    {
        return !empty($this->initiatorIdentifier) && !empty($this->securityCredential);
    }
}
