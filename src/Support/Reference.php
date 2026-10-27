<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Support;

use GrupoFixIT\Mpesa\Exceptions\ValidationException;

final readonly class Reference
{
    public const MAX_TRANSACTION_REFERENCE_LENGTH = 20;

    /**
     * Gera uma referência alfanumérica única (máximo 20 caracteres por exigência Vodacom).
     */
    public static function generate(int $length = 16, string $prefix = ''): string
    {
        $prefix = preg_replace('/[^A-Za-z0-9]/', '', $prefix) ?? '';
        $remainingLength = min($length, self::MAX_TRANSACTION_REFERENCE_LENGTH) - strlen($prefix);

        if ($remainingLength <= 0) {
            return substr($prefix, 0, self::MAX_TRANSACTION_REFERENCE_LENGTH);
        }

        /** @var int<1, max> $bytesCount */
        $bytesCount = max(1, (int) ceil($remainingLength / 2));
        $bytes = random_bytes($bytesCount);
        $randomHex = strtoupper(bin2hex($bytes));

        $randomPart = substr($randomHex, 0, $remainingLength);

        return $prefix . $randomPart;
    }

    /**
     * Valida TransactionReference (tamanho entre 1 e 20 caracteres alfanuméricos).
     */
    public static function validateTransactionReference(string $reference): void
    {
        $trimmed = trim($reference);
        $len = strlen($trimmed);

        if ($len < 1 || $len > self::MAX_TRANSACTION_REFERENCE_LENGTH) {
            throw new ValidationException(sprintf(
                'TransactionReference inválida: "%s". O comprimento deve ser entre 1 e 20 caracteres (recebido: %d).',
                $reference,
                $len
            ));
        }

        if (!preg_match('/^[A-Za-z0-9]+$/', $trimmed)) {
            throw new ValidationException(sprintf(
                'TransactionReference inválida: "%s". Deve conter apenas caracteres alfanuméricos (A-Z, a-z, 0-9).',
                $reference
            ));
        }
    }

    /**
     * Valida ThirdPartyReference (não vazia e sem caracteres de controlo).
     */
    public static function validateThirdPartyReference(string $reference): void
    {
        $trimmed = trim($reference);

        if ($trimmed === '') {
            throw new ValidationException('ThirdPartyReference não pode ser vazia.');
        }

        if (strlen($trimmed) > 64) {
            throw new ValidationException(sprintf(
                'ThirdPartyReference demasiado longa (máximo 64 caracteres, recebido: %d).',
                strlen($trimmed)
            ));
        }
    }
}
