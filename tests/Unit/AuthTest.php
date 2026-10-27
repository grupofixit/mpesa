<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Tests\Unit;

use GrupoFixIT\Mpesa\Auth\CachedTokenProvider;
use GrupoFixIT\Mpesa\Auth\TokenGenerator;
use GrupoFixIT\Mpesa\Config\Credentials;
use GrupoFixIT\Mpesa\Exceptions\AuthenticationException;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

final class AuthTest extends TestCase
{
    private string $publicKeyPem;
    private string $privateKeyPem;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var array{public_key: string, private_key: string} $keys */
        $keys = require dirname(__DIR__) . '/Fixtures/test_rsa_keys.php';
        $this->publicKeyPem = $keys['public_key'];
        $this->privateKeyPem = $keys['private_key'];
    }

    public function testTokenGeneratorEncryptsAndCanBeDecrypted(): void
    {
        $apiKey = 'super_secret_vodacom_api_key_12345';
        $credentials = new Credentials($apiKey, $this->publicKeyPem, '171717');

        $token = TokenGenerator::generate($credentials);

        $this->assertNotEmpty($token);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/]+={0,2}$/', $token);

        // Descriptografa com a chave privada para confirmar exatidão da criptografia RSA
        $binaryEncrypted = base64_decode($token, true);
        $this->assertIsString($binaryEncrypted);

        $privateKey = openssl_pkey_get_private($this->privateKeyPem);
        $this->assertNotFalse($privateKey);

        $decrypted = '';
        $success = openssl_private_decrypt($binaryEncrypted, $decrypted, $privateKey, OPENSSL_PKCS1_PADDING);

        $this->assertTrue($success);
        $this->assertSame($apiKey, $decrypted);
    }

    public function testTokenGeneratorThrowsOnInvalidPublicKey(): void
    {
        $this->expectException(AuthenticationException::class);
        $credentials = new Credentials('key', 'NOT_A_VALID_RSA_KEY', '171717');

        TokenGenerator::generate($credentials);
    }

    public function testCachedTokenProviderMemoryMemoization(): void
    {
        $credentials = new Credentials('my-key', $this->publicKeyPem, '171717');
        $provider = new CachedTokenProvider($credentials);

        $token1 = $provider->getToken();
        $token2 = $provider->getToken();

        $this->assertSame($token1, $token2);

        $provider->invalidateToken();
        // Após invalidar, um novo token é gerado (devido ao padding aleatório RSA)
        $token3 = $provider->getToken();
        $this->assertNotEmpty($token3);
    }

    public function testCachedTokenProviderWithPsr16Cache(): void
    {
        $credentials = new Credentials('my-key', $this->publicKeyPem, '171717');

        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $cache->expects($this->once())
            ->method('set')
            ->with($this->anything(), $this->isType('string'), 3300)
            ->willReturn(true);

        $provider = new CachedTokenProvider($credentials, $cache, 3300);
        $token = $provider->getToken();

        $this->assertNotEmpty($token);
    }
}
