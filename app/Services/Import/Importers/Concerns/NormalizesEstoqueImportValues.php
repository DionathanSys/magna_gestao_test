<?php

namespace App\Services\Import\Importers\Concerns;

trait NormalizesEstoqueImportValues
{
    private function value(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && filled($row[$key])) {
                return $row[$key];
            }
        }

        return null;
    }

    private function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeIdentifier(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_numeric($value)) {
            $floatValue = (float) $value;
            $integerValue = (int) $floatValue;

            if ((float) $integerValue === $floatValue) {
                return (string) $integerValue;
            }

            return rtrim(rtrim(number_format($floatValue, 4, '.', ''), '0'), '.');
        }

        return trim((string) $value);
    }

    private function toDecimal(mixed $value): float
    {
        $normalized = $this->normalizeNumeric($value);

        return is_numeric($normalized) ? round((float) $normalized, 4) : 0.0;
    }

    private function toCents(mixed $value): int
    {
        $normalized = $this->normalizeNumeric($value);

        return is_numeric($normalized) ? (int) round(((float) $normalized) * 100) : 0;
    }

    private function normalizeNumeric(mixed $value): string
    {
        if ($value === null) {
            return '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $normalized = trim((string) $value);

        if ($normalized === '') {
            return '0';
        }

        if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
            if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }

            return $normalized;
        }

        if (str_contains($normalized, ',')) {
            return str_replace(',', '.', $normalized);
        }

        return $normalized;
    }
}
