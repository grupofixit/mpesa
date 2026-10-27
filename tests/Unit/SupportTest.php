<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Tests\Unit;

use GrupoFixIT\Mpesa\Exceptions\ValidationException;
use GrupoFixIT\Mpesa\Support\Money;
use GrupoFixIT\Mpesa\Support\Msisdn;
use GrupoFixIT\Mpesa\Support\Redactor;
use GrupoFixIT\Mpesa\Support\Reference;
use PHPUnit\Framework\TestCase;

final class SupportTest extends TestCase
{
    public function testMoneyCreationAndFormatting(): void
    {
        $money = Money::mzn('100.50');
        $this->assertSame('100.50', $money->getAmount());
        $this->assertSame(10050, $money->toCents());
        $this->assertSame('MZN', $money->getCurrency());
        $this->assertSame('100.50 MZN', (string) $money);

        $integerMoney = Money::mzn(250);
        $this->assertSame('250.00', $integerMoney->getAmount());
        $this->assertSame(25000, $integerMoney->toCents());

        $floatMoney = Money::mzn(19.99);
        $this->assertSame('19.99', $floatMoney->getAmount());
    }

    public function testMoneyArithmeticAndComparison(): void
    {
        $a = Money::mzn('50.25');
        $b = Money::mzn('25.50');

        $sum = $a->add($b);
        $this->assertSame('75.75', $sum->getAmount());

        $diff = $a->subtract($b);
        $this->assertSame('24.75', $diff->getAmount());

        $this->assertTrue($a->isGreaterThan($b));
        $this->assertTrue($b->isLessThan($a));
        $this->assertTrue($a->equals(Money::mzn('50.25')));
    }

    public function testMoneyRejectsNegativeAndZero(): void
    {
        $this->expectException(ValidationException::class);
        Money::mzn('0.00');
    }

    public function testMoneyRejectsInvalidFormat(): void
    {
        $this->expectException(ValidationException::class);
        Money::mzn('abc');
    }

    public function testMoneyRejectsMoreThanTwoDecimals(): void
    {
        $this->expectException(ValidationException::class);
        Money::mzn('10.999');
    }

    public function testMsisdnNormalization(): void
    {
        $msisdn1 = Msisdn::from('258841234567');
        $this->assertSame('258841234567', $msisdn1->getValue());
        $this->assertSame('25884****567', $msisdn1->masked());

        // Com '+' e espaços
        $msisdn2 = Msisdn::from('+258 85 987 6543');
        $this->assertSame('258859876543', $msisdn2->getValue());
        $this->assertSame('25885****543', $msisdn2->masked());

        // Apenas 9 dígitos
        $msisdn3 = Msisdn::from('841234567');
        $this->assertSame('258841234567', $msisdn3->getValue());
    }

    public function testMsisdnRejectsInvalidOperatorPrefix(): void
    {
        $this->expectException(ValidationException::class);
        // 82 não é operador M-Pesa Vodacom
        Msisdn::from('258821234567');
    }

    public function testMsisdnRejectsInvalidLength(): void
    {
        $this->expectException(ValidationException::class);
        Msisdn::from('12345');
    }

    public function testReferenceGenerationAndValidation(): void
    {
        $ref = Reference::generate(16, 'TRX');
        $this->assertStringStartsWith('TRX', $ref);
        $this->assertLessThanOrEqual(20, strlen($ref));

        Reference::validateTransactionReference($ref);
        Reference::validateThirdPartyReference('EXT_REF_123');

        $this->assertTrue(true);
    }

    public function testReferenceRejectsInvalidTransactionReference(): void
    {
        $this->expectException(ValidationException::class);
        // Excede 20 caracteres
        Reference::validateTransactionReference('THIS_REFERENCE_IS_FAR_TOO_LONG_FOR_VODACOM');
    }

    public function testReferenceRejectsSpecialCharacters(): void
    {
        $this->expectException(ValidationException::class);
        Reference::validateTransactionReference('TRX-123-INVALID!');
    }

    public function testRedactorMasksSecrets(): void
    {
        $data = [
            'apiKey' => 'super-secret-key-12345',
            'securityCredential' => 'sec-cred-999',
            'input_CustomerMSISDN' => '258841234567',
            'public_info' => 'Vodacom Mpesa',
            'nested' => [
                'token' => 'jwt-token-value',
                'amount' => '100.00',
            ],
        ];

        $redacted = Redactor::redactArray($data);

        $this->assertSame('[REDACTED]', $redacted['apiKey']);
        $this->assertSame('[REDACTED]', $redacted['securityCredential']);
        $this->assertSame('25884****567', $redacted['input_CustomerMSISDN']);
        $this->assertSame('Vodacom Mpesa', $redacted['public_info']);
        $this->assertSame('[REDACTED]', $redacted['nested']['token']);
        $this->assertSame('100.00', $redacted['nested']['amount']);

        $bearerString = 'Authorization: Bearer mySecretToken123456';
        $this->assertSame('Authorization: Bearer [REDACTED]', Redactor::redactString($bearerString));
    }
}
