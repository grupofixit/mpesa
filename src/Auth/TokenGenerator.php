<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Auth;

use GrupoFixIT\Mpesa\Config\Credentials;
use GrupoFixIT\Mpesa\Exceptions\AuthenticationException;

final readonly class TokenGenerator
{
    /**
     * Cifra a apiKey com a chave pública RSA da Vodacom (PKCS#1 v1.5) e codifica em base64.
     *
     * @throws AuthenticationException se a cifragem falhar
     */
    public static function generate(Credentials $credentials): string
    {
        $publicKeyPem = $credentials->getFormattedPublicKey();
        $apiKey = $credentials->apiKey;

        $encrypted = '';
        /** @var string $encrypted */
        $success = @openssl_public_encrypt($apiKey, $encrypted, $publicKeyPem, OPENSSL_PKCS1_PADDING);

        if (!$success || $encrypted === '') {
            $error = openssl_error_string();
            $message = ($error !== false && $error !== '') ? $error : 'Chave pública inválida ou erro interno do OpenSSL';
            throw new AuthenticationException(sprintf(
                'Falha ao cifrar o token M-Pesa com a chave pública RSA: %s',
                $message
            ));
        }

        return base64_encode($encrypted);
    }
}
