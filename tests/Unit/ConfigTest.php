<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Tests\Unit;

use GrupoFixIT\Mpesa\Config\Credentials;
use GrupoFixIT\Mpesa\Config\MpesaConfig;
use GrupoFixIT\Mpesa\Enums\Environment;
use GrupoFixIT\Mpesa\Exceptions\ConfigurationException;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testCredentialsValidCreation(): void
    {
        $creds = new Credentials(
            apiKey: 'valid-api-key',
            publicKey: 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC...',
            serviceProviderCode: '171717',
            initiatorIdentifier: 'MPesa2018',
            securityCredential: 'SecretPassword123',
        );

        $this->assertSame('valid-api-key', $creds->apiKey);
        $this->assertSame('171717', $creds->serviceProviderCode);
        $this->assertTrue($creds->hasReversalCredentials());

        $formatted = $creds->getFormattedPublicKey();
        $this->assertStringStartsWith("-----BEGIN PUBLIC KEY-----\n", $formatted);
        $this->assertStringEndsWith("\n-----END PUBLIC KEY-----\n", $formatted);
    }

    public function testCredentialsRejectsEmptyApiKey(): void
    {
        $this->expectException(ConfigurationException::class);
        new Credentials('', 'public-key', '171717');
    }

    public function testCredentialsRejectsEmptyPublicKey(): void
    {
        $this->expectException(ConfigurationException::class);
        new Credentials('api-key', '   ', '171717');
    }

    public function testCredentialsRejectsNonNumericServiceProviderCode(): void
    {
        $this->expectException(ConfigurationException::class);
        new Credentials('api-key', 'public-key', 'NOT_NUMERIC');
    }

    public function testMpesaConfigValidCreation(): void
    {
        $creds = new Credentials('key', 'pub', '171717');
        $config = new MpesaConfig(
            environment: Environment::Sandbox,
            credentials: $creds,
            connectTimeout: 15,
            timeout: 45,
        );

        $this->assertSame(Environment::Sandbox, $config->environment);
        $this->assertSame(15, $config->connectTimeout);
        $this->assertSame(45, $config->timeout);
    }

    public function testMpesaConfigRejectsInvalidTimeout(): void
    {
        $this->expectException(ConfigurationException::class);
        $creds = new Credentials('key', 'pub', '171717');
        new MpesaConfig(
            environment: Environment::Sandbox,
            credentials: $creds,
            timeout: -5,
        );
    }

    public function testMpesaConfigRejectsInvalidCustomBaseUrl(): void
    {
        $this->expectException(ConfigurationException::class);
        $creds = new Credentials('key', 'pub', '171717');
        new MpesaConfig(
            environment: Environment::Sandbox,
            credentials: $creds,
            customBaseUrl: 'not-a-valid-url',
        );
    }
}
