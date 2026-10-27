<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Tests\Unit;

use GrupoFixIT\Mpesa\Config\Credentials;
use GrupoFixIT\Mpesa\Config\MpesaConfig;
use GrupoFixIT\Mpesa\Enums\Environment;
use GrupoFixIT\Mpesa\Enums\Operation;
use GrupoFixIT\Mpesa\Enums\ResponseCode;
use GrupoFixIT\Mpesa\Enums\TransactionStatus;
use GrupoFixIT\Mpesa\Exceptions\OutcomeUnknownException;
use GrupoFixIT\Mpesa\Exceptions\ValidationException;
use GrupoFixIT\Mpesa\Requests\B2BRequest;
use GrupoFixIT\Mpesa\Requests\B2CRequest;
use GrupoFixIT\Mpesa\Requests\C2BRequest;
use GrupoFixIT\Mpesa\Requests\CustomerNameRequest;
use GrupoFixIT\Mpesa\Requests\ReversalRequest;
use GrupoFixIT\Mpesa\Requests\TransactionStatusRequest;
use GrupoFixIT\Mpesa\Support\Money;
use GrupoFixIT\Mpesa\Support\Msisdn;
use GrupoFixIT\Mpesa\Support\Reference;
use GrupoFixIT\Mpesa\Testing\FakeMpesa;
use PHPUnit\Framework\TestCase;

final class MpesaClientTest extends TestCase
{
    private FakeMpesa $fake;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fake = FakeMpesa::create();
    }

    public function testC2BSuccess(): void
    {
        $this->fake->fakeC2BSuccess('CONV_123', 'TRX_456', 'REF_789');

        $request = new C2BRequest(
            transactionReference: Reference::generate(12, 'TRX'),
            customerMsisdn: Msisdn::from('258841234567'),
            amount: Money::mzn('150.00'),
            thirdPartyReference: 'REF_789',
        );

        $result = $this->fake->getClient()->c2b($request);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(ResponseCode::Success, $result->responseCode);
        $this->assertSame('CONV_123', $result->conversationId);
        $this->assertSame('TRX_456', $result->transactionId);
        $this->assertSame('REF_789', $result->thirdPartyReference);

        $recorded = $this->fake->getFakeTransport()->getLastRequest();
        $this->assertNotNull($recorded);
        $this->assertSame(Operation::C2B, $recorded['endpoint']->operation);
        $this->assertSame('150.00', $recorded['payload']['input_Amount']);
    }

    /**
     * REGRA INEGOCIÁVEL: Timeout no C2B NUNCA reenvia e lança OutcomeUnknownException.
     */
    public function testC2BTimeoutThrowsOutcomeUnknownExceptionAndNeverRetries(): void
    {
        $this->fake->fakeNetworkFailure('Gateway Timeout');

        $request = new C2BRequest(
            transactionReference: Reference::generate(12, 'TRX'),
            customerMsisdn: Msisdn::from('258841234567'),
            amount: Money::mzn('200.00'),
            thirdPartyReference: 'MY_UNIQUE_REF_001',
        );

        try {
            $this->fake->getClient()->c2b($request);
            $this->fail('Deveria ter lançado OutcomeUnknownException.');
        } catch (OutcomeUnknownException $e) {
            $this->assertSame('MY_UNIQUE_REF_001', $e->getThirdPartyReference());
            $this->assertSame(Operation::C2B, $e->getOperation());
        }

        // Garante que o transporte foi chamado EXATAMENTE 1 vez (zero retries)
        $this->assertSame(1, $this->fake->getFakeTransport()->countRequests());
    }

    public function testTransactionStatusSuccess(): void
    {
        $this->fake->fakeStatusSuccess('Completed', 'CONV_123', 'REF_123');

        $request = new TransactionStatusRequest(
            thirdPartyReference: 'REF_123',
            queryReference: 'CONV_123',
        );

        $result = $this->fake->getClient()->transactionStatus($request);

        $this->assertTrue($result->isSuccess());
        $this->assertSame(TransactionStatus::Completed, $result->transactionStatus);
        $this->assertTrue($result->transactionStatus->isTerminal());
        $this->assertSame('CONV_123', $result->conversationId);
    }

    public function testTransactionStatusRetriesOnTransientError(): void
    {
        // Falha 1: 503 Service Unavailable
        $this->fake->getFakeTransport()->enqueueResponse(503, ['error' => 'overloaded']);
        // Tentativa 2: Sucesso 200
        $this->fake->fakeStatusSuccess('Completed', 'CONV_123', 'REF_123');

        $request = new TransactionStatusRequest(
            thirdPartyReference: 'REF_123',
            queryReference: 'CONV_123',
        );

        $result = $this->fake->getClient()->transactionStatus($request);

        $this->assertSame(TransactionStatus::Completed, $result->transactionStatus);
        $this->assertSame(2, $this->fake->getFakeTransport()->countRequests());
    }

    public function testCustomerNameSuccess(): void
    {
        $this->fake->fakeCustomerNameSuccess('Jo** Sil**', 'CONV_888', 'REF_888');

        $request = new CustomerNameRequest(
            customerMsisdn: Msisdn::from('258851234567'),
            thirdPartyReference: 'REF_888',
        );

        $result = $this->fake->getClient()->customerName($request);

        $this->assertTrue($result->isSuccess());
        $this->assertSame('Jo** Sil**', $result->customerName);
        $this->assertSame('Jo**', $result->firstNameMasked);
        $this->assertSame('Sil**', $result->secondNameMasked);
    }

    public function testB2CAndB2BSuccess(): void
    {
        $this->fake->fakeC2BSuccess('CONV_B2C', 'TRX_B2C', 'REF_B2C');

        $b2cReq = new B2CRequest(
            customerMsisdn: Msisdn::from('258841234567'),
            amount: Money::mzn('500.00'),
            transactionReference: Reference::generate(12, 'B2C'),
            thirdPartyReference: 'REF_B2C',
        );

        $b2cRes = $this->fake->getClient()->b2c($b2cReq);
        $this->assertTrue($b2cRes->isSuccess());

        $this->fake->fakeC2BSuccess('CONV_B2B', 'TRX_B2B', 'REF_B2B');

        $b2bReq = new B2BRequest(
            transactionReference: Reference::generate(12, 'B2B'),
            amount: Money::mzn('1000.00'),
            thirdPartyReference: 'REF_B2B',
            receiverPartyCode: '979797',
        );

        $b2bRes = $this->fake->getClient()->b2b($b2bReq);
        $this->assertTrue($b2bRes->isSuccess());
    }

    public function testReversalSuccess(): void
    {
        $this->fake->fakeReversalSuccess('TRX_REV_1', 'CONV_REV_1', 'REF_REV_1');

        $req = new ReversalRequest(
            transactionId: 'ORIGINAL_TRX_999',
            thirdPartyReference: 'REF_REV_1',
            reversalAmount: Money::mzn('50.00'), // Estorno parcial
        );

        $result = $this->fake->getClient()->reversal($req);

        $this->assertTrue($result->isSuccess());
        $lastReq = $this->fake->getFakeTransport()->getLastRequest();
        $this->assertNotNull($lastReq);
        $this->assertSame('PUT', $lastReq['endpoint']->method());
        $this->assertSame('50.00', $lastReq['payload']['input_ReversalAmount']);
    }

    public function testReversalWithoutCredentialsThrowsValidationException(): void
    {
        /** @var array{public_key: string, private_key: string} $keys */
        $keys = require dirname(__DIR__) . '/Fixtures/test_rsa_keys.php';

        // Configuração sem initiatorIdentifier e sem securityCredential
        $configWithoutReversal = new MpesaConfig(
            environment: Environment::Sandbox,
            credentials: new Credentials('api-key', $keys['public_key'], '171717'),
        );

        $fakeWithoutReversal = FakeMpesa::create($configWithoutReversal);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('SecurityCredential é obrigatória');

        $req = new ReversalRequest(
            transactionId: 'TRX_123',
            thirdPartyReference: 'REF_REV_2'
        );

        $fakeWithoutReversal->getClient()->reversal($req);
    }

    public function testCallbackParsingAndAcknowledgement(): void
    {
        $rawCallback = json_encode([
            'input_OriginalConversationID' => 'conv_vodacom_999',
            'input_ThirdPartyReference' => 'ref_app_999',
            'input_TransactionID' => 'trx_vodacom_888',
            'input_ResultCode' => '0',
            'input_ResultDesc' => 'Request Processed Successfully',
        ], JSON_THROW_ON_ERROR);

        $payload = $this->fake->getClient()->parseCallback($rawCallback);

        $this->assertSame('conv_vodacom_999', $payload->originalConversationId);
        $this->assertSame('ref_app_999', $payload->thirdPartyReference);
        $this->assertSame('trx_vodacom_888', $payload->transactionId);
        $this->assertTrue($payload->isSuccess());

        $ack = $this->fake->getClient()->acknowledgeCallback($payload);
        $ackArray = $ack->toArray();

        $this->assertSame('conv_vodacom_999', $ackArray['output_OriginalConversationID']);
        $this->assertSame('ref_app_999', $ackArray['output_ThirdPartyConversationID']);
        $this->assertSame('0', $ackArray['output_ResponseCode']);
        $this->assertSame('Successfully Accepted Result', $ackArray['output_ResponseDesc']);
    }
}
