<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa;

use GrupoFixIT\Mpesa\Auth\CachedTokenProvider;
use GrupoFixIT\Mpesa\Auth\TokenProviderInterface;
use GrupoFixIT\Mpesa\Callback\CallbackAcknowledgement;
use GrupoFixIT\Mpesa\Callback\CallbackParser;
use GrupoFixIT\Mpesa\Callback\CallbackPayload;
use GrupoFixIT\Mpesa\Config\MpesaConfig;
use GrupoFixIT\Mpesa\Enums\Operation;
use GrupoFixIT\Mpesa\Exceptions\ApiException;
use GrupoFixIT\Mpesa\Exceptions\OutcomeUnknownException;
use GrupoFixIT\Mpesa\Exceptions\TransportException;
use GrupoFixIT\Mpesa\Http\Endpoint;
use GrupoFixIT\Mpesa\Http\RetryPolicy;
use GrupoFixIT\Mpesa\Http\Transport;
use GrupoFixIT\Mpesa\Http\TransportInterface;
use GrupoFixIT\Mpesa\Requests\B2BRequest;
use GrupoFixIT\Mpesa\Requests\B2CRequest;
use GrupoFixIT\Mpesa\Requests\C2BRequest;
use GrupoFixIT\Mpesa\Requests\CustomerNameRequest;
use GrupoFixIT\Mpesa\Requests\ReversalRequest;
use GrupoFixIT\Mpesa\Requests\TransactionStatusRequest;
use GrupoFixIT\Mpesa\Responses\CustomerNameResult;
use GrupoFixIT\Mpesa\Responses\ReversalResult;
use GrupoFixIT\Mpesa\Responses\TransactionResult;
use GrupoFixIT\Mpesa\Responses\TransactionStatusResult;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\ResponseInterface;

final readonly class MpesaClient
{
    public function __construct(
        private MpesaConfig $config,
        private TransportInterface $transport,
        private TokenProviderInterface $tokenProvider,
        private RetryPolicy $retryPolicy = new RetryPolicy(),
    ) {
    }

    /**
     * Factory padrão para inicialização da SDK com auto-descoberta PSR-18/17 e TLS estritamente verificado.
     */
    public static function create(
        MpesaConfig $config,
        ?TransportInterface $customTransport = null,
        ?TokenProviderInterface $customTokenProvider = null,
    ): self {
        $tokenProvider = $customTokenProvider ?? new CachedTokenProvider(
            credentials: $config->credentials,
            cache: $config->cache,
            ttl: $config->tokenTtl,
        );

        if ($customTransport !== null) {
            $transport = $customTransport;
        } else {
            $factory = new HttpFactory();
            $requestFactory = $config->requestFactory ?? $factory;
            $streamFactory = $config->streamFactory ?? $factory;

            $verify = $config->caBundle;
            if ($verify === null) {
                $envCa = getenv('MPESA_CA_BUNDLE');
                if (is_string($envCa) && $envCa !== '' && file_exists($envCa)) {
                    $verify = $envCa;
                } elseif (PHP_OS_FAMILY === 'Windows' && file_exists('C:\\wamp\\bin\\php\\php8.3.14\\extras\\ssl\\cacert.pem')) {
                    $verify = 'C:\\wamp\\bin\\php\\php8.3.14\\extras\\ssl\\cacert.pem';
                } else {
                    $verify = true;
                }
            }

            // TLS SEMPRE ativado (verify => true ou caminho de CA bundle válido). Nunca desativar!
            $httpClient = $config->httpClient ?? new GuzzleClient([
                'timeout' => $config->timeout,
                'connect_timeout' => $config->connectTimeout,
                'verify' => $verify,
                'http_errors' => false,
            ]);

            $transport = new Transport(
                client: $httpClient,
                requestFactory: $requestFactory,
                streamFactory: $streamFactory,
                logger: $config->logger,
                origin: $config->origin,
            );
        }

        return new self($config, $transport, $tokenProvider);
    }

    /**
     * C2B Single Stage - Cobrança ao cliente via USSD push.
     *
     * @throws OutcomeUnknownException em caso de timeout/falha de rede (NUNCA reenviar!)
     * @throws ApiException em caso de erro retornado pela Vodacom
     */
    public function c2b(C2BRequest $request): TransactionResult
    {
        $endpoint = new Endpoint(Operation::C2B, $this->config->environment, $this->config->customBaseUrl);
        $payload = $request->toPayload($this->config->credentials->serviceProviderCode);

        $response = $this->executeMoneyMovement($endpoint, Operation::C2B, $request->thirdPartyReference, $payload);

        return TransactionResult::fromPsrResponse($response);
    }

    /**
     * B2C - Pagamento a cliente.
     *
     * @throws OutcomeUnknownException em caso de timeout/falha de rede (NUNCA reenviar!)
     * @throws ApiException
     */
    public function b2c(B2CRequest $request): TransactionResult
    {
        $endpoint = new Endpoint(Operation::B2C, $this->config->environment, $this->config->customBaseUrl);
        $payload = $request->toPayload($this->config->credentials->serviceProviderCode);

        $response = $this->executeMoneyMovement($endpoint, Operation::B2C, $request->thirdPartyReference, $payload);

        return TransactionResult::fromPsrResponse($response);
    }

    /**
     * B2B - Transferência entre empresas.
     *
     * @throws OutcomeUnknownException em caso de timeout/falha de rede (NUNCA reenviar!)
     * @throws ApiException
     */
    public function b2b(B2BRequest $request): TransactionResult
    {
        $endpoint = new Endpoint(Operation::B2B, $this->config->environment, $this->config->customBaseUrl);
        $payload = $request->toPayload($this->config->credentials->serviceProviderCode);

        $response = $this->executeMoneyMovement($endpoint, Operation::B2B, $request->thirdPartyReference, $payload);

        return TransactionResult::fromPsrResponse($response);
    }

    /**
     * Reversal - Estorno total ou parcial de um pagamento.
     *
     * @throws OutcomeUnknownException em caso de timeout/falha de rede (NUNCA reenviar!)
     * @throws ApiException
     */
    public function reversal(ReversalRequest $request): ReversalResult
    {
        $endpoint = new Endpoint(Operation::Reversal, $this->config->environment, $this->config->customBaseUrl);
        $payload = $request->toPayload($this->config->credentials);

        $response = $this->executeMoneyMovement($endpoint, Operation::Reversal, $request->thirdPartyReference, $payload);

        return ReversalResult::fromPsrResponse($response);
    }

    /**
     * Query Transaction Status - Consulta de estado da transação.
     * Operação de leitura segura com suporte a retry automático em 5xx/queda transitória.
     *
     * @throws TransportException
     * @throws ApiException
     */
    public function transactionStatus(TransactionStatusRequest $request): TransactionStatusResult
    {
        $endpoint = new Endpoint(Operation::TransactionStatus, $this->config->environment, $this->config->customBaseUrl);
        $payload = $request->toPayload($this->config->credentials->serviceProviderCode);

        $response = $this->executeReadOnly($endpoint, Operation::TransactionStatus, $payload);

        return TransactionStatusResult::fromPsrResponse($response);
    }

    /**
     * Query Customer Name - Consulta do nome (ofuscado) do cliente.
     * Operação de leitura segura com suporte a retry automático em 5xx/queda transitória.
     *
     * @throws TransportException
     * @throws ApiException
     */
    public function customerName(CustomerNameRequest $request): CustomerNameResult
    {
        $endpoint = new Endpoint(Operation::CustomerName, $this->config->environment, $this->config->customBaseUrl);
        $payload = $request->toPayload($this->config->credentials->serviceProviderCode);

        $response = $this->executeReadOnly($endpoint, Operation::CustomerName, $payload);

        return CustomerNameResult::fromPsrResponse($response);
    }

    /**
     * Analisa o payload de callback recebido da Vodacom.
     *
     * @param string|array<string, mixed> $input
     */
    public function parseCallback(string|array $input): CallbackPayload
    {
        return CallbackParser::parse($input);
    }

    /**
     * Gera a confirmação (acknowledgement) no formato estrito exigido pela Vodacom.
     */
    public function acknowledgeCallback(CallbackPayload $payload): CallbackAcknowledgement
    {
        return CallbackAcknowledgement::forPayload($payload);
    }

    public function getConfig(): MpesaConfig
    {
        return $this->config;
    }

    public function getTransport(): TransportInterface
    {
        return $this->transport;
    }

    public function getTokenProvider(): TokenProviderInterface
    {
        return $this->tokenProvider;
    }

    /**
     * Executa operação que move dinheiro: SEM RETRY AUTOMÁTICO em falhas.
     * Se falhar por timeout ou erro de rede, lança OutcomeUnknownException com thirdPartyReference.
     *
     * @param array<string, mixed> $payload
     */
    private function executeMoneyMovement(
        Endpoint $endpoint,
        Operation $operation,
        string $thirdPartyReference,
        array $payload
    ): ResponseInterface {
        $token = $this->tokenProvider->getToken();

        try {
            $response = $this->transport->send($endpoint, $token, $payload);
        } catch (TransportException $e) {
            // REGRA DE OURO: Nunca repetir chamada de dinheiro. Lançar OutcomeUnknownException.
            throw new OutcomeUnknownException(
                thirdPartyReference: $thirdPartyReference,
                operation: $operation,
                message: sprintf('Falha de rede/timeout durante %s. O estado é incerto e deve ser consultado com Status Query.', $operation->value),
                previous: $e
            );
        }

        if ($response->getStatusCode() === 401) {
            $this->tokenProvider->invalidateToken();
        }

        return $response;
    }

    /**
     * Executa operação de leitura: RETRIES PERMITIDOS para 5xx/quedas de rede.
     *
     * @param array<string, mixed> $payload
     */
    private function executeReadOnly(
        Endpoint $endpoint,
        Operation $operation,
        array $payload
    ): ResponseInterface {
        $attempt = 0;

        while (true) {
            $token = $this->tokenProvider->getToken();

            try {
                $response = $this->transport->send($endpoint, $token, $payload);
                $statusCode = $response->getStatusCode();

                if ($statusCode === 401) {
                    $this->tokenProvider->invalidateToken();
                }

                if ($this->retryPolicy->canRetry($operation, $attempt, $statusCode)) {
                    $attempt++;
                    usleep($this->retryPolicy->getDelayMs($attempt) * 1000);
                    continue;
                }

                return $response;
            } catch (TransportException $e) {
                if ($this->retryPolicy->canRetry($operation, $attempt, 0)) {
                    $attempt++;
                    usleep($this->retryPolicy->getDelayMs($attempt) * 1000);
                    continue;
                }

                throw $e;
            }
        }
    }
}
