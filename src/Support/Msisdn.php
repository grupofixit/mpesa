<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Support;

use GrupoFixIT\Mpesa\Exceptions\ValidationException;

final readonly class Msisdn
{
    public const COUNTRY_CODE = '258';
    public const VALID_PREFIXES = ['84', '85'];

    public string $value;

    private function __construct(string $normalized)
    {
        $this->value = $normalized;
    }

    public function __get(string $name): string
    {
        if ($name === 'e164' || $name === 'value') {
            return $this->value;
        }

        throw new \InvalidArgumentException("Propriedade [{$name}] não existe em Msisdn.");
    }

    /**
     * Cria e normaliza um MSISDN de Moçambique.
     * Aceita formatos: '258841234567', '+258841234567', '841234567', '85 123 4567', etc.
     */
    public static function from(string $input): self
    {
        // Remove espaços, traços, parênteses e '+'
        $clean = preg_replace('/[\s\-\(\)\+]/', '', trim($input)) ?? '';

        if (!ctype_digit($clean)) {
            throw new ValidationException(sprintf(
                'Número de telefone inválido: "%s". Deve conter apenas dígitos.',
                $input
            ));
        }

        // Se tem 9 dígitos (ex: 841234567), adiciona o código do país 258
        if (strlen($clean) === 9) {
            $clean = self::COUNTRY_CODE . $clean;
        }

        // Deve ter exatamente 12 dígitos (258 + 9 dígitos)
        if (strlen($clean) !== 12 || !str_starts_with($clean, self::COUNTRY_CODE)) {
            throw new ValidationException(sprintf(
                'Número de telefone inválido: "%s". Deve ser um número de Moçambique com prefixo 258 (12 dígitos).',
                $input
            ));
        }

        // Valida o prefixo do operador (M-Pesa Moçambique usa 84 ou 85)
        $operatorPrefix = substr($clean, 3, 2);
        if (!in_array($operatorPrefix, self::VALID_PREFIXES, true)) {
            throw new ValidationException(sprintf(
                'Prefixo de operadora inválido para M-Pesa: "%s". Operadores válidos: %s.',
                $operatorPrefix,
                implode(', ', self::VALID_PREFIXES)
            ));
        }

        return new self($clean);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Retorna o número mascarado para logs e auditoria (ex: 25884****567).
     */
    public function masked(): string
    {
        // 25884 (5 chars) + '****' + 567 (últimos 3 chars)
        $prefix = substr($this->value, 0, 5);
        $suffix = substr($this->value, -3);

        return sprintf('%s****%s', $prefix, $suffix);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
