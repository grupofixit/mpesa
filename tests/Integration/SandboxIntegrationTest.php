<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Tests\Integration;

use GrupoFixIT\Mpesa\Config\Credentials;
use GrupoFixIT\Mpesa\Config\MpesaConfig;
use GrupoFixIT\Mpesa\Enums\Environment;
use GrupoFixIT\Mpesa\MpesaClient;
use GrupoFixIT\Mpesa\Requests\C2BRequest;
use GrupoFixIT\Mpesa\Requests\CustomerNameRequest;
use GrupoFixIT\Mpesa\Requests\TransactionStatusRequest;
use GrupoFixIT\Mpesa\Support\Money;
use GrupoFixIT\Mpesa\Support\Msisdn;
use GrupoFixIT\Mpesa\Support\Reference;
use PHPUnit\Framework\TestCase;

/**
 * @group sandbox
 */
final class SandboxIntegrationTest extends TestCase
{
    private ?MpesaClient $client = null;

    protected function setUp(): void
    {
        parent::setUp();

        $apiKey = getenv('MPESA_SANDBOX_API_KEY');
        $publicKey = getenv('MPESA_SANDBOX_PUBLIC_KEY');

        if (!$apiKey || !$publicKey) {
            $this->markTestSkipped('Credenciais do sandbox M-Pesa não fornecidas em MPESA_SANDBOX_API_KEY / MPESA_SANDBOX_PUBLIC_KEY.');
        }

        $config = new MpesaConfig(
            environment: Environment::Sandbox,
            credentials: new Credentials(
                apiKey: (string) $apiKey,
                publicKey: (string) $publicKey,
                serviceProviderCode: (string) (getenv('MPESA_SANDBOX_SERVICE_PROVIDER_CODE') ?: '171717'),
            )
        );

        $this->client = MpesaClient::create($config);
    }

    public function testSandboxCustomerNameQuery(): void
    {
        $this->assertNotNull($this->client);

        $req = new CustomerNameRequest(
            customerMsisdn: Msisdn::from('258843330333'),
            thirdPartyReference: Reference::generate(12, 'NAME'),
        );

        $res = $this->client->customerName($req);
        $this->assertTrue($res->isSuccess());
        $this->assertNotEmpty($res->customerName);
    }

    public function testSandboxC2BAndStatusQuery(): void
    {
        $this->assertNotNull($this->client);

        $thirdPartyRef = Reference::generate(12, 'TP');
        $c2bReq = new C2BRequest(
            transactionReference: Reference::generate(12, 'TRX'),
            customerMsisdn: Msisdn::from('258843330333'),
            amount: Money::mzn('10.00'),
            thirdPartyReference: $thirdPartyRef,
        );

        $c2bRes = $this->client->c2b($c2bReq);
        $this->assertTrue($c2bRes->isSuccess());
        $this->assertNotNull($c2bRes->conversationId);

        $statusReq = new TransactionStatusRequest(
            thirdPartyReference: $thirdPartyRef,
            queryReference: $c2bRes->conversationId,
        );

        $statusRes = $this->client->transactionStatus($statusReq);
        $this->assertTrue($statusRes->isSuccess());
    }
}
