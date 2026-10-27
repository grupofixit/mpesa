<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Exceptions;

use GrupoFixIT\Mpesa\Enums\Operation;

class OutcomeUnknownException extends MpesaException
{
    /**
     * @param string $thirdPartyReference Referência única da transação para consulta de estado
     * @param Operation $operation Operação financeira afetada
     * @param string $message Mensagem descritiva do erro
     * @param \Throwable|null $previous Causa original (timeout, queda de rede, etc.)
     */
    public function __construct(
        private readonly string $thirdPartyReference,
        private readonly Operation $operation,
        string $message = 'O desfecho da operação financeira é desconhecido devido a timeout ou falha transitória de rede. NUNCA reenvie a operação; consulte o estado com Status Query.',
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('%s [Operação: %s, ThirdPartyReference: %s]', $message, $this->operation->value, $this->thirdPartyReference),
            0,
            $previous
        );
    }

    public function getThirdPartyReference(): string
    {
        return $this->thirdPartyReference;
    }

    public function getOperation(): Operation
    {
        return $this->operation;
    }
}
