<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Enums;

enum TransactionStatus: string
{
    case Completed = 'Completed';
    case Pending = 'Pending';
    case Cancelled = 'Cancelled';
    case Expired = 'Expired';
    case Failed = 'Failed';
    case NotAvailable = 'N/A';
    case Unknown = 'Unknown';

    /**
     * Determina se o estado é final e definitivo.
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Completed, self::Cancelled, self::Expired, self::Failed => true,
            self::Pending, self::NotAvailable, self::Unknown => false,
        };
    }

    /**
     * Determina se a transação foi concluída com sucesso.
     */
    public function isSuccessful(): bool
    {
        return $this === self::Completed;
    }

    /**
     * Determina se a transação ainda está em processamento.
     */
    public function isPending(): bool
    {
        return match ($this) {
            self::Pending, self::NotAvailable => true,
            default => false,
        };
    }

    /**
     * Converte string recebida da Vodacom de forma segura.
     */
    public static function fromRaw(?string $raw): self
    {
        if ($raw === null || trim($raw) === '') {
            return self::Unknown;
        }

        $trimmed = trim($raw);

        return self::tryFrom($trimmed) ?? match (strtoupper($trimmed)) {
            'COMPLETED', 'SUCCESS', 'SUCCESSFUL' => self::Completed,
            'PENDING', 'PROCESSING' => self::Pending,
            'CANCELLED', 'CANCELED' => self::Cancelled,
            'EXPIRED' => self::Expired,
            'FAILED' => self::Failed,
            'N/A', 'NA', 'NOT AVAILABLE' => self::NotAvailable,
            default => self::Unknown,
        };
    }
}
