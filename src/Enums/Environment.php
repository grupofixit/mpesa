<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Enums;

enum Environment: string
{
    case Sandbox = 'sandbox';
    case Live = 'live';

    public function isSandbox(): bool
    {
        return $this === self::Sandbox;
    }

    public function isLive(): bool
    {
        return $this === self::Live;
    }

    public function defaultBaseUrl(): string
    {
        return match ($this) {
            self::Sandbox => 'https://api.sandbox.vm.co.mz',
            self::Live => 'https://api.vm.co.mz',
        };
    }
}
