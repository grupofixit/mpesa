<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Config;

use GrupoFixIT\Mpesa\Enums\Environment;
use GrupoFixIT\Mpesa\Enums\Operation;

final readonly class EnvironmentRegistry
{
    public const DEFAULT_ORIGIN = 'developer.mpesa.vm.co.mz';

    public static function getPort(Operation $operation): int
    {
        return match ($operation) {
            Operation::C2B => 18352,
            Operation::B2C => 18345,
            Operation::B2B => 18349,
            Operation::TransactionStatus => 18353,
            Operation::Reversal => 18354,
            Operation::CustomerName => 19323,
        };
    }

    public static function getPath(Operation $operation): string
    {
        return match ($operation) {
            Operation::C2B => '/ipg/v1x/c2bPayment/singleStage/',
            Operation::B2C => '/ipg/v1x/b2cPayment/',
            Operation::B2B => '/ipg/v1x/b2bPayment/',
            Operation::TransactionStatus => '/ipg/v1x/queryTransactionStatus/',
            Operation::Reversal => '/ipg/v1x/reversal/',
            Operation::CustomerName => '/ipg/v1x/queryCustomerName/',
        };
    }

    public static function getHttpMethod(Operation $operation): string
    {
        return match ($operation) {
            Operation::C2B, Operation::B2C, Operation::B2B => 'POST',
            Operation::TransactionStatus, Operation::CustomerName => 'GET',
            Operation::Reversal => 'PUT',
        };
    }

    public static function buildUrl(Environment $environment, Operation $operation, ?string $customBaseUrl = null): string
    {
        $base = $customBaseUrl ?? $environment->defaultBaseUrl();
        $base = rtrim($base, '/');
        $port = self::getPort($operation);
        $path = self::getPath($operation);

        return sprintf('%s:%d%s', $base, $port, $path);
    }
}
