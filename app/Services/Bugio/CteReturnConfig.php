<?php

namespace App\Services\Bugio;

class CteReturnConfig
{
    public function allowedSenders(): array
    {
        return collect(db_config('config-bugio.cte-return-senders', []))
            ->map(function (mixed $row): string {
                if (is_array($row)) {
                    return strtolower(trim((string) ($row['email'] ?? '')));
                }

                return strtolower(trim((string) $row));
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function isAllowedSender(?string $email): bool
    {
        $email = strtolower(trim((string) $email));

        return $email !== '' && in_array($email, $this->allowedSenders(), true);
    }

    public function extractDocumentoTransporte(string $subject): ?string
    {
        if (preg_match('/\b(BG-\d{4,})\b/i', $subject, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }
}
