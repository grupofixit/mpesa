<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Support;

final readonly class Redactor
{
    public const REDACTED_MARKER = '[REDACTED]';

    public const SENSITIVE_KEYS = [
        'apikey',
        'api_key',
        'publickey',
        'public_key',
        'securitycredential',
        'security_credential',
        'input_securitycredential',
        'token',
        'access_token',
        'authorization',
        'secret',
        'webhook_secret',
        'password',
    ];

    /**
     * Redige segredos de um array recursivamente.
     *
     * @param array<string|int, mixed> $data
     * @return array<string|int, mixed>
     */
    public static function redactArray(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (in_array($normalizedKey, self::SENSITIVE_KEYS, true)) {
                $result[$key] = self::REDACTED_MARKER;
                continue;
            }

            if (in_array($normalizedKey, ['input_customermsisdn', 'customermsisdn', 'msisdn', 'phone'], true) && is_string($value)) {
                $result[$key] = self::maskMsisdn($value);
                continue;
            }

            if (is_array($value)) {
                $result[$key] = self::redactArray($value);
            } elseif (is_string($value)) {
                $result[$key] = self::redactString($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Redige tokens em strings (ex.: cabeçalhos 'Bearer ...' ou padrões de segredos).
     */
    public static function redactString(string $input): string
    {
        // Redige 'Bearer <token>'
        $output = preg_replace('/(Bearer\s+)[A-Za-z0-9\+\/\=_\.\-]+/i', '$1' . self::REDACTED_MARKER, $input) ?? $input;

        // Redige chaves no formato 'apiKey=...' ou '"apiKey": "..."'
        $output = preg_replace('/("(?:apiKey|securityCredential|token)":\s*")[^"]+(")/i', '$1' . self::REDACTED_MARKER . '$2', $output) ?? $output;

        return $output;
    }

    /**
     * Mascara número MSISDN.
     */
    public static function maskMsisdn(string $msisdn): string
    {
        $digits = preg_replace('/[^\d]/', '', $msisdn) ?? '';
        $len = strlen($digits);

        if ($len < 8) {
            return self::REDACTED_MARKER;
        }

        $start = substr($digits, 0, min(5, $len - 3));
        $end = substr($digits, -3);

        return sprintf('%s****%s', $start, $end);
    }
}
