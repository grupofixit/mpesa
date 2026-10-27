<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Tests\Unit;

use GrupoFixIT\Mpesa\Config\EnvironmentRegistry;
use GrupoFixIT\Mpesa\Enums\Environment;
use GrupoFixIT\Mpesa\Enums\Operation;
use PHPUnit\Framework\TestCase;

final class EnvironmentRegistryTest extends TestCase
{
    public function testPortsAndMethodsForOperations(): void
    {
        $this->assertSame(18352, EnvironmentRegistry::getPort(Operation::C2B));
        $this->assertSame('POST', EnvironmentRegistry::getHttpMethod(Operation::C2B));

        $this->assertSame(18345, EnvironmentRegistry::getPort(Operation::B2C));
        $this->assertSame('POST', EnvironmentRegistry::getHttpMethod(Operation::B2C));

        $this->assertSame(18349, EnvironmentRegistry::getPort(Operation::B2B));
        $this->assertSame('POST', EnvironmentRegistry::getHttpMethod(Operation::B2B));

        $this->assertSame(18353, EnvironmentRegistry::getPort(Operation::TransactionStatus));
        $this->assertSame('GET', EnvironmentRegistry::getHttpMethod(Operation::TransactionStatus));

        $this->assertSame(18354, EnvironmentRegistry::getPort(Operation::Reversal));
        $this->assertSame('PUT', EnvironmentRegistry::getHttpMethod(Operation::Reversal));

        $this->assertSame(19323, EnvironmentRegistry::getPort(Operation::CustomerName));
        $this->assertSame('GET', EnvironmentRegistry::getHttpMethod(Operation::CustomerName));
    }

    public function testUrlBuilding(): void
    {
        $sandboxC2B = EnvironmentRegistry::buildUrl(Environment::Sandbox, Operation::C2B);
        $this->assertSame('https://api.sandbox.vm.co.mz:18352/ipg/v1x/c2bPayment/singleStage/', $sandboxC2B);

        $liveStatus = EnvironmentRegistry::buildUrl(Environment::Live, Operation::TransactionStatus);
        $this->assertSame('https://api.vm.co.mz:18353/ipg/v1x/queryTransactionStatus/', $liveStatus);

        $customUrl = EnvironmentRegistry::buildUrl(Environment::Sandbox, Operation::CustomerName, 'https://mock.local');
        $this->assertSame('https://mock.local:19323/ipg/v1x/queryCustomerName/', $customUrl);
    }
}
