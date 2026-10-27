<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Auth;

interface TokenProviderInterface
{
    /**
     * Obtém o token de autenticação (usando cache se disponível).
     *
     * @param bool $forceRefresh Se verdadeiro, ignora o cache e gera novo token.
     */
    public function getToken(bool $forceRefresh = false): string;

    /**
     * Invalida o token em cache (chamado quando a API devolver 401).
     */
    public function invalidateToken(): void;
}
