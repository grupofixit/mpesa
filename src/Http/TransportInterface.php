<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Http;

use Psr\Http\Message\ResponseInterface;

interface TransportInterface
{
    /**
     * Envia uma requisição HTTP à API Vodacom M-Pesa.
     *
     * @param Endpoint $endpoint Destino da requisição
     * @param string $token Token de autorização cifrado
     * @param array<string, mixed> $payload Campos a enviar (corpo em POST/PUT ou query em GET)
     * @param array<string, string> $extraHeaders Cabeçalhos adicionais
     * @return ResponseInterface
     * @throws \GrupoFixIT\Mpesa\Exceptions\TransportException em falha de conexão/TLS
     */
    public function send(
        Endpoint $endpoint,
        string $token,
        array $payload = [],
        array $extraHeaders = []
    ): ResponseInterface;
}
