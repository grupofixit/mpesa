<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Support;

use GrupoFixIT\Mpesa\Exceptions\ValidationException;
use InvalidArgumentException;

final readonly class Money
{
    public const DEFAULT_CURRENCY = 'MZN';

    private int $cents;
    private string $currency;

    private function __construct(int $cents, string $currency = self::DEFAULT_CURRENCY)
    {
        if ($cents <= 0) {
            throw new ValidationException('O valor monetário deve ser estritamente positivo (> 0).');
        }

        $this->cents = $cents;
        $this->currency = strtoupper(trim($currency));
    }

    /**
     * Cria uma instância de Money a partir de uma string decimal ou inteiro (ex: '100.50' ou 100).
     */
    public static function mzn(string|int|float $amount): self
    {
        return self::of($amount, self::DEFAULT_CURRENCY);
    }

    /**
     * Cria uma instância com moeda arbitrária.
     */
    public static function of(string|int|float $amount, string $currency = self::DEFAULT_CURRENCY): self
    {
        if (is_float($amount)) {
            // Converte float com 2 casas para evitar imprecisões binárias
            $formatted = sprintf('%.2f', $amount);
        } elseif (is_int($amount)) {
            $formatted = sprintf('%d.00', $amount);
        } else {
            $trimmed = trim($amount);
            if (!preg_match('/^\d+(\.\d{1,2})?$/', $trimmed)) {
                throw new ValidationException(sprintf(
                    'Valor monetário inválido: "%s". Deve ser um número decimal positivo com até 2 casas decimais.',
                    $amount
                ));
            }
            $parts = explode('.', $trimmed);
            $integerPart = $parts[0];
            $decimalPart = isset($parts[1]) ? str_pad($parts[1], 2, '0') : '00';
            $formatted = sprintf('%s.%s', $integerPart, $decimalPart);
        }

        $cents = (int) round((float) $formatted * 100);

        return new self($cents, $currency);
    }

    /**
     * Cria a partir de centavos inteiros.
     */
    public static function fromCents(int $cents, string $currency = self::DEFAULT_CURRENCY): self
    {
        return new self($cents, $currency);
    }

    public function toCents(): int
    {
        return $this->cents;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * Retorna o valor em formato decimal com 2 casas (ex: "100.50").
     */
    public function getAmount(): string
    {
        $dollars = intdiv($this->cents, 100);
        $remainder = abs($this->cents % 100);

        return sprintf('%d.%02d', $dollars, $remainder);
    }

    /**
     * Alias para getAmount().
     */
    public function formatted(): string
    {
        return $this->getAmount();
    }

    public function add(self $other): self
    {
        $this->ensureSameCurrency($other);

        return new self($this->cents + $other->cents, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->ensureSameCurrency($other);

        return new self($this->cents - $other->cents, $this->currency);
    }

    public function isGreaterThan(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->cents > $other->cents;
    }

    public function isLessThan(self $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->cents < $other->cents;
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents && $this->currency === $other->currency;
    }

    public function __toString(): string
    {
        return sprintf('%s %s', $this->getAmount(), $this->currency);
    }

    private function ensureSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(sprintf(
                'Não é possível comparar/operar moedas diferentes: %s e %s',
                $this->currency,
                $other->currency
            ));
        }
    }
}
