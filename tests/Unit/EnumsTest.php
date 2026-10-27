<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Tests\Unit;

use GrupoFixIT\Mpesa\Enums\Environment;
use GrupoFixIT\Mpesa\Enums\Operation;
use GrupoFixIT\Mpesa\Enums\ResponseCodeCategory;
use GrupoFixIT\Mpesa\Enums\TransactionStatus;
use PHPUnit\Framework\TestCase;

final class EnumsTest extends TestCase
{
    public function testEnvironmentProperties(): void
    {
        $sandbox = Environment::Sandbox;
        $live = Environment::Live;

        $this->assertTrue($sandbox->isSandbox());
        $this->assertFalse($sandbox->isLive());
        $this->assertStringContainsString('sandbox', $sandbox->defaultBaseUrl());

        $this->assertTrue($live->isLive());
        $this->assertFalse($live->isSandbox());
        $this->assertSame('https://api.vm.co.mz', $live->defaultBaseUrl());
    }

    public function testOperationMoneyMovementClassification(): void
    {
        $this->assertTrue(Operation::C2B->isMoneyMovement());
        $this->assertTrue(Operation::B2C->isMoneyMovement());
        $this->assertTrue(Operation::B2B->isMoneyMovement());
        $this->assertTrue(Operation::Reversal->isMoneyMovement());

        $this->assertFalse(Operation::TransactionStatus->isMoneyMovement());
        $this->assertFalse(Operation::CustomerName->isMoneyMovement());

        $this->assertTrue(Operation::TransactionStatus->isReadOnly());
        $this->assertTrue(Operation::CustomerName->isReadOnly());
    }

    public function testTransactionStatusClassification(): void
    {
        $this->assertTrue(TransactionStatus::Completed->isTerminal());
        $this->assertTrue(TransactionStatus::Completed->isSuccessful());
        $this->assertTrue(TransactionStatus::Cancelled->isTerminal());
        $this->assertTrue(TransactionStatus::Expired->isTerminal());
        $this->assertTrue(TransactionStatus::Failed->isTerminal());

        $this->assertFalse(TransactionStatus::Pending->isTerminal());
        $this->assertTrue(TransactionStatus::Pending->isPending());
        $this->assertTrue(TransactionStatus::NotAvailable->isPending());
        $this->assertFalse(TransactionStatus::Unknown->isTerminal());
    }

    public function testTransactionStatusFromRaw(): void
    {
        $this->assertSame(TransactionStatus::Completed, TransactionStatus::fromRaw('Completed'));
        $this->assertSame(TransactionStatus::Completed, TransactionStatus::fromRaw('completed'));
        $this->assertSame(TransactionStatus::Pending, TransactionStatus::fromRaw('Pending'));
        $this->assertSame(TransactionStatus::Cancelled, TransactionStatus::fromRaw('Cancelled'));
        $this->assertSame(TransactionStatus::Expired, TransactionStatus::fromRaw('Expired'));
        $this->assertSame(TransactionStatus::Failed, TransactionStatus::fromRaw('Failed'));
        $this->assertSame(TransactionStatus::NotAvailable, TransactionStatus::fromRaw('N/A'));
        $this->assertSame(TransactionStatus::Unknown, TransactionStatus::fromRaw(null));
        $this->assertSame(TransactionStatus::Unknown, TransactionStatus::fromRaw('SomeRandomStatus'));
    }

    public function testResponseCodeCategory(): void
    {
        $this->assertTrue(ResponseCodeCategory::Success->isSuccess());
        $this->assertFalse(ResponseCodeCategory::ClientError->isSuccess());
        $this->assertTrue(ResponseCodeCategory::Retryable->isRetryable());
    }
}
