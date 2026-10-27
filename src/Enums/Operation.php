<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Enums;

enum Operation: string
{
    case C2B = 'c2b';
    case B2C = 'b2c';
    case B2B = 'b2b';
    case TransactionStatus = 'transaction_status';
    case CustomerName = 'customer_name';
    case Reversal = 'reversal';

    /**
     * Indica se a operação move fundos financeiros.
     * Operações que movem dinheiro NUNCA sofrem retries automáticos em falhas de rede/timeout.
     */
    public function isMoneyMovement(): bool
    {
        return match ($this) {
            self::C2B, self::B2C, self::B2B, self::Reversal => true,
            self::TransactionStatus, self::CustomerName => false,
        };
    }

    /**
     * Indica se a operação é segura/idempotente para retry automático em leitura.
     */
    public function isReadOnly(): bool
    {
        return !$this->isMoneyMovement();
    }
}
