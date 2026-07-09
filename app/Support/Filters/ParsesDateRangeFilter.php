<?php

namespace App\Support\Filters;

use Carbon\Carbon;

trait ParsesDateRangeFilter
{
    protected function parseDateRangeFilter(mixed $value): array
    {
        $start = null;
        $end = null;

        try {
            if (is_array($value) && count($value) === 2) {
                $start = Carbon::parse($value[0])->startOfDay();
                $end = Carbon::parse($value[1])->endOfDay();
            } elseif (is_string($value) && str_contains($value, ' - ')) {
                [$rawStart, $rawEnd] = array_map('trim', explode(' - ', $value, 2));
                $start = Carbon::createFromFormat('d/m/Y', $rawStart)->startOfDay();
                $end = Carbon::createFromFormat('d/m/Y', $rawEnd)->endOfDay();
            } elseif (is_string($value) && filled($value)) {
                $start = Carbon::createFromFormat('d/m/Y', trim($value))->startOfDay();
                $end = Carbon::createFromFormat('d/m/Y', trim($value))->endOfDay();
            }
        } catch (\Throwable) {
            return [null, null];
        }

        return [$start, $end];
    }
}
