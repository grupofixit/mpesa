<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Testing;

use GrupoFixIT\Mpesa\Http\Endpoint;
use GrupoFixIT\Mpesa\Http\TransportInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class FakeTransport implements TransportInterface
{
    /** @var array<int, array{endpoint: Endpoint, token: string, payload: array<string, mixed>, extraHeaders: array<string, string>}> */
    private array $recordedRequests = [];

    /** @var array<int, ResponseInterface|\Throwable> */
    private array $responseQueue = [];

    /**
     * Enfileira uma resposta simulada.
     *
     * @param array<string, mixed>|string $body
     * @param array<string, string|array<string>> $headers
     */
    public function enqueueResponse(int $statusCode = 200, array|string $body = [], array $headers = []): self
    {
        /** @var array<string, string|array<string>> $mergedHeaders */
        $mergedHeaders = array_merge(['Content-Type' => 'application/json'], $headers);
        $bodyString = is_array($body) ? json_encode($body, JSON_THROW_ON_ERROR) : $body;

        $this->responseQueue[] = new Response($statusCode, $mergedHeaders, $bodyString);

        return $this;
    }

    /**
     * Enfileira uma exceção a ser lançada no send.
     */
    public function enqueueException(\Throwable $exception): self
    {
        $this->responseQueue[] = $exception;

        return $this;
    }

    public function send(
        Endpoint $endpoint,
        string $token,
        array $payload = [],
        array $extraHeaders = []
    ): ResponseInterface {
        $this->recordedRequests[] = [
            'endpoint' => $endpoint,
            'token' => $token,
            'payload' => $payload,
            'extraHeaders' => $extraHeaders,
        ];

        if (empty($this->responseQueue)) {
            throw new RuntimeException('Nenhuma resposta enfileirada no FakeTransport.');
        }

        $next = array_shift($this->responseQueue);

        if ($next instanceof \Throwable) {
            throw $next;
        }

        return $next;
    }

    /**
     * @return array<int, array{endpoint: Endpoint, token: string, payload: array<string, mixed>, extraHeaders: array<string, string>}>
     */
    public function getRecordedRequests(): array
    {
        return $this->recordedRequests;
    }

    /**
     * @return array{endpoint: Endpoint, token: string, payload: array<string, mixed>, extraHeaders: array<string, string>}|null
     */
    public function getLastRequest(): ?array
    {
        if (empty($this->recordedRequests)) {
            return null;
        }

        return $this->recordedRequests[array_key_last($this->recordedRequests)];
    }

    public function countRequests(): int
    {
        return count($this->recordedRequests);
    }

    public function reset(): void
    {
        $this->recordedRequests = [];
        $this->responseQueue = [];
    }
}
