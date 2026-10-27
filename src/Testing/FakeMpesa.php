<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Testing;

use GrupoFixIT\Mpesa\Auth\CachedTokenProvider;
use GrupoFixIT\Mpesa\Config\Credentials;
use GrupoFixIT\Mpesa\Config\MpesaConfig;
use GrupoFixIT\Mpesa\Enums\Environment;
use GrupoFixIT\Mpesa\Exceptions\TransportException;
use GrupoFixIT\Mpesa\MpesaClient;

final class FakeMpesa
{
    private MpesaClient $client;
    private FakeTransport $fakeTransport;

    public function __construct(
        MpesaConfig $config,
        FakeTransport $fakeTransport,
    ) {
        $this->fakeTransport = $fakeTransport;
        $tokenProvider = new CachedTokenProvider($config->credentials);

        $this->client = new MpesaClient(
            config: $config,
            transport: $this->fakeTransport,
            tokenProvider: $tokenProvider
        );
    }

    public static function create(?MpesaConfig $config = null): self
    {
        $fakeTransport = new FakeTransport();

        /** @var array{public_key: string, private_key: string} $keys */
        $keys = require dirname(__DIR__, 2) . '/tests/Fixtures/test_rsa_keys.php';

        $resolvedConfig = $config ?? new MpesaConfig(
            environment: Environment::Sandbox,
            credentials: new Credentials(
                apiKey: 'test-api-key',
                publicKey: $keys['public_key'],
                serviceProviderCode: '171717',
                initiatorIdentifier: 'TestInitiator',
                securityCredential: 'TestSecurityCredential',
            ),
        );

        return new self($resolvedConfig, $fakeTransport);
    }

    public function getClient(): MpesaClient
    {
        return $this->client;
    }

    public function getFakeTransport(): FakeTransport
    {
        return $this->fakeTransport;
    }

    public function fakeC2BSuccess(
        string $conversationId = 'AG_20261026_001',
        string $transactionId = 'TRX_4XDF12345',
        string $thirdPartyReference = 'REF123456',
    ): self {
        $this->fakeTransport->enqueueResponse(200, [
            'output_ResponseCode' => 'INS-0',
            'output_ResponseDesc' => 'Request processed successfully',
            'output_ConversationID' => $conversationId,
            'output_TransactionID' => $transactionId,
            'output_ThirdPartyReference' => $thirdPartyReference,
        ]);

        return $this;
    }

    public function fakeStatusSuccess(
        string $status = 'Completed',
        string $conversationId = 'AG_20261026_001',
        string $thirdPartyReference = 'REF123456',
    ): self {
        $this->fakeTransport->enqueueResponse(200, [
            'output_ResponseCode' => 'INS-0',
            'output_ResponseDesc' => 'Request processed successfully',
            'output_ConversationID' => $conversationId,
            'output_ThirdPartyReference' => $thirdPartyReference,
            'output_ResponseTransactionStatus' => $status,
        ]);

        return $this;
    }

    public function fakeCustomerNameSuccess(
        string $customerName = 'Jo** Sil**',
        string $conversationId = 'AG_20261026_001',
        string $thirdPartyReference = 'REF123456',
    ): self {
        $this->fakeTransport->enqueueResponse(200, [
            'output_ResponseCode' => 'INS-0',
            'output_ResponseDesc' => 'Request processed successfully',
            'output_ConversationID' => $conversationId,
            'output_ThirdPartyReference' => $thirdPartyReference,
            'output_CustomerName' => $customerName,
        ]);

        return $this;
    }

    public function fakeReversalSuccess(
        string $transactionId = 'TRX_4XDF12345',
        string $conversationId = 'AG_20261026_001',
        string $thirdPartyReference = 'REF123456',
    ): self {
        $this->fakeTransport->enqueueResponse(200, [
            'output_ResponseCode' => 'INS-0',
            'output_ResponseDesc' => 'Request processed successfully',
            'output_ConversationID' => $conversationId,
            'output_TransactionID' => $transactionId,
            'output_ThirdPartyReference' => $thirdPartyReference,
        ]);

        return $this;
    }

    public function fakeNetworkFailure(string $message = 'Conexão interrompida / Timeout'): self
    {
        $this->fakeTransport->enqueueException(new TransportException($message));

        return $this;
    }

    public function fakeApiError(
        int $httpStatus = 400,
        string $code = 'INS-2006',
        string $desc = 'Insufficient balance',
    ): self {
        $this->fakeTransport->enqueueResponse($httpStatus, [
            'output_ResponseCode' => $code,
            'output_ResponseDesc' => $desc,
        ]);

        return $this;
    }
}
