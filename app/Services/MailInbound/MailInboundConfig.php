<?php

namespace App\Services\MailInbound;

use App\Services\MailInbound\Support\DocumentIdentity;
use Illuminate\Support\Facades\Log;

class MailInboundConfig
{
    public function enabled(): bool
    {
        return (bool) db_config('config-mail-inbound.enabled', true);
    }

    public function allowedSenders(): array
    {
        $raw = db_config('config-mail-inbound.allowed_senders', []);
        $bugioReturnSenders = db_config('config-bugio.cte-return-senders', []);

        $senders = collect([$raw, $bugioReturnSenders])
            ->flatten(1)
            ->map(function ($row) {
                if (is_array($row)) {
                    return strtolower(trim((string) ($row['email'] ?? '')));
                }

                return strtolower(trim((string) $row));
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        Log::info('Remetentes permitidos carregados da configuracao', [
            'raw' => $raw,
            'bugio_return_senders' => $bugioReturnSenders,
            'normalized' => $senders,
        ]);

        return $senders;
    }

    public function saleRecipientDocument(): ?string
    {
        $value = db_config('config-mail-inbound.sale_recipient_document');

        if ($value === null || $value === '') {
            $value = db_config('config-mail-inbound.bugio_recipient_cnpj');
        }

        return DocumentIdentity::normalizeDigits(
            $value
        );
    }

    public function issuerDocument(): ?string
    {
        return DocumentIdentity::normalizeDigits(
            db_config('config-mail-inbound.issuer_document')
        );
    }

    public function unidadeNegocio(): ?string
    {
        $value = trim((string) db_config('config-mail-inbound.unidade_negocio', ''));

        return $value !== '' ? $value : null;
    }
}
