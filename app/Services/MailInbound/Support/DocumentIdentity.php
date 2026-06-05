<?php

namespace App\Services\MailInbound\Support;

class DocumentIdentity
{
    public static function normalizeDigits(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return $digits !== '' ? $digits : null;
    }

    public static function normalizePlate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $plate = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $value));

        return $plate !== '' ? $plate : null;
    }
}
