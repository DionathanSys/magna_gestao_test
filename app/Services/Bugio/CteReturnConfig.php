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
        $subject = $this->decodeSubject($subject);

        if (preg_match('/\b(BG-\d{4,})\b/i', $subject, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    public function extractCorrelationCode(string $subject): ?string
    {
        $subject = $this->decodeSubject($subject);

        if (preg_match('/\b(CTE-REQ-[A-HJKMNP-TV-Z0-9]{20,})\b/i', $subject, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    protected function decodeSubject(string $subject): string
    {
        if (str_contains($subject, '=?')) {
            $decoded = iconv_mime_decode($subject, 0, 'UTF-8');

            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $subject;
    }
}
