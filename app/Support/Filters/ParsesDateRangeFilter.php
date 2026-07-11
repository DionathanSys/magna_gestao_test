<?php

namespace App\Support\Filters;

use Carbon\Carbon;
use Illuminate\Support\Str;

trait ParsesDateRangeFilter
{
    protected function parseDateRangeFilter(mixed $value): array
    {
        $start = null;
        $end = null;

        try {
            if (is_array($value) && count($value) === 2) {
                $start = $this->parseDateFilterValue($value[0])?->startOfDay();
                $end = $this->parseDateFilterValue($value[1])?->endOfDay();
            } elseif (is_string($value) && str_contains($value, ' - ')) {
                [$rawStart, $rawEnd] = array_map('trim', explode(' - ', $value, 2));
                $start = $this->parseDateFilterValue($rawStart)?->startOfDay();
                $end = $this->parseDateFilterValue($rawEnd)?->endOfDay();
            } elseif (is_string($value) && filled($value)) {
                $start = $this->parseDateFilterValue(trim($value))?->startOfDay();
                $end = $this->parseDateFilterValue(trim($value))?->endOfDay();
            }
        } catch (\Throwable) {
            return [null, null];
        }

        return [$start, $end];
    }

    protected function parseDateFilterValue(mixed $value): ?Carbon
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value) === 1) {
            return Carbon::createFromFormat('d/m/Y', $value);
        }

        $isoDate = Str::of($value)->before('T');

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $isoDate->value()) === 1) {
            return Carbon::createFromFormat('Y-m-d', $isoDate->value());
        }

        return Carbon::parse($value);
    }
}
