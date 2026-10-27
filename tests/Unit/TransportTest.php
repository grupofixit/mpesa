<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Tests\Unit;

use GrupoFixIT\Mpesa\Config\EnvironmentRegistry;
use GrupoFixIT\Mpesa\Config\MpesaConfig;
use GrupoFixIT\Mpesa\Enums\Environment;
use GrupoFixIT\Mpesa\Enums\Operation;
use GrupoFixIT\Mpesa\Exceptions\TransportException;
use GrupoFixIT\Mpesa\Http\Endpoint;
use GrupoFixIT\Mpesa\Http\Transport;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;

final class TransportTest extends TestCase
{
    private HttpFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new HttpFactory();
    }

    public function testSendPostBuildsHeadersAndBody(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) {
                $this->assertSame('POST', $request->getMethod());
                $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
                $this->assertSame('Bearer test_encrypted_token_123', $request->getHeaderLine('Authorization'));
                $this->assertSame(EnvironmentRegistry::DEFAULT_ORIGIN, $request->getHeaderLine('Origin'));

                $body = (string) $request->getBody();
                $data = json_decode($body, true);
                $this->assertIsArray($data);
                $this->assertSame('100.00', $data['input_Amount']);

                return new Response(200, [], json_encode(['output_ResponseCode' => 'INS-0']));
            });

        $transport = new Transport(
            client: $mockClient,
            requestFactory: $this->factory,
            streamFactory: $this->factory,
        );

        $endpoint = new Endpoint(Operation::C2B, Environment::Sandbox);
        $response = $transport->send($endpoint, 'test_encrypted_token_123', ['input_Amount' => '100.00']);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testSendGetAppendsQueryString(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->willReturnCallback(function (RequestInterface $request) {
                $this->assertSame('GET', $request->getMethod());
                $uri = (string) $request->getUri();
                $this->assertStringContainsString('input_QueryReference=12345', $uri);

                return new Response(200, [], json_encode(['output_ResponseCode' => 'INS-0']));
            });

        $transport = new Transport(
            client: $mockClient,
            requestFactory: $this->factory,
            streamFactory: $this->factory,
        );

        $endpoint = new Endpoint(Operation::TransactionStatus, Environment::Sandbox);
        $response = $transport->send($endpoint, 'token', ['input_QueryReference' => '12345']);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testSendThrowsTransportExceptionOnNetworkFailure(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $networkException = new class('Conexão recusada') extends \Exception implements ClientExceptionInterface {
        };

        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->willThrowException($networkException);

        $transport = new Transport(
            client: $mockClient,
            requestFactory: $this->factory,
            streamFactory: $this->factory,
        );

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Falha de rede/transporte');

        $endpoint = new Endpoint(Operation::C2B, Environment::Sandbox);
        $transport->send($endpoint, 'token', []);
    }

    public function testTransportLogsWithRedactedSecrets(): void
    {
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('sendRequest')
            ->willReturn(new Response(200, [], '{}'));

        $mockLogger = $this->createMock(LoggerInterface::class);
        $mockLogger->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(function (string $message, array $context) {
                if (isset($context['payload'])) {
                    $this->assertIsArray($context['payload']);
                    if (isset($context['payload']['apiKey'])) {
                        $this->assertSame('[REDACTED]', $context['payload']['apiKey']);
                    }
                }
            });

        $transport = new Transport(
            client: $mockClient,
            requestFactory: $this->factory,
            streamFactory: $this->factory,
            logger: $mockLogger,
        );

        $endpoint = new Endpoint(Operation::C2B, Environment::Sandbox);
        $transport->send($endpoint, 'token', ['apiKey' => 'super-secret', 'amount' => '100.00']);
    }

    /**
     * Regra Inegociável: A verificação TLS nunca pode ser desativada por configuração na SDK.
     */
    public function testTlsVerificationCannotBeDisabledByDesign(): void
    {
        $transportRef = new ReflectionClass(Transport::class);
        $this->assertFalse($transportRef->hasProperty('verifyTls'), 'Transport não pode ter propriedade para desativar TLS');
        $this->assertFalse($transportRef->hasMethod('disableTls'), 'Transport não pode ter método para desativar TLS');

        $configRef = new ReflectionClass(MpesaConfig::class);
        $this->assertFalse($configRef->hasProperty('verifyTls'), 'MpesaConfig não pode ter opção para desativar TLS');
        $this->assertFalse($configRef->hasProperty('skipTls'), 'MpesaConfig não pode ter opção para saltar TLS');
    }
}
