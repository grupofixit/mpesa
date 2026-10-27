<?php

declare(strict_types=1);

namespace GrupoFixIT\Mpesa\Enums;

enum ResponseCodeCategory: string
{
    case Success = 'success';
    case Retryable = 'retryable';
    case ClientError = 'client_error';
    case FinalFailure = 'final_failure';
    case NeedsReview = 'needs_review';
    case Unknown = 'unknown';

    public function isSuccess(): bool
    {
        return $this === self::Success;
    }

    public function isRetryable(): bool
    {
        return $this === self::Retryable;
    }
}
