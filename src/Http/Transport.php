<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Http;

use GrupoFixIT\Mpesa\Config\EnvironmentRegistry;
use GrupoFixIT\Mpesa\Exceptions\TransportException;
use GrupoFixIT\Mpesa\Support\Redactor;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final readonly class Transport implements TransportInterface
{
    public function __construct(
        private ClientInterface $client,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private ?LoggerInterface $logger = null,
        private string $origin = EnvironmentRegistry::DEFAULT_ORIGIN,
    ) {
    }

    public function send(
        Endpoint $endpoint,
        string $token,
        array $payload = [],
        array $extraHeaders = []
    ): ResponseInterface {
        $method = $endpoint->method();
        $url = $endpoint->url();

        if ($method === 'GET' && !empty($payload)) {
            $queryString = http_build_query($payload);
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . $queryString;
        }

        $request = $this->requestFactory->createRequest($method, $url);
        $request = $request
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('Origin', $this->origin);

        foreach ($extraHeaders as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if (in_array($method, ['POST', 'PUT'], true)) {
            $jsonBody = json_encode($payload, JSON_THROW_ON_ERROR);
            $stream = $this->streamFactory->createStream($jsonBody);
            $request = $request->withBody($stream);
        }

        if ($this->logger !== null) {
            $this->logger->info('Enviando requisição M-Pesa', [
                'method' => $method,
                'url' => $url,
                'payload' => Redactor::redactArray($payload),
            ]);
        }

        try {
            $response = $this->client->sendRequest($request);

            if ($this->logger !== null) {
                $this->logger->info('Resposta recebida M-Pesa', [
                    'status' => $response->getStatusCode(),
                ]);
            }

            return $response;
        } catch (ClientExceptionInterface $e) {
            if ($this->logger !== null) {
                $this->logger->error('Erro de transporte na comunicação com a Vodacom M-Pesa', [
                    'message' => $e->getMessage(),
                ]);
            }

            throw new TransportException(
                sprintf('Falha de rede/transporte ao comunicar com M-Pesa: %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        } catch (\Throwable $e) {
            throw new TransportException(
                sprintf('Erro inesperado no transporte HTTP: %s', $e->getMessage()),
                (int) $e->getCode(),
                $e
            );
        }
    }
}
