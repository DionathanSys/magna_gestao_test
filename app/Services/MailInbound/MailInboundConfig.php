<?php

namespace App\Services\MailInbound;

class MailInboundConfig
{
    public function enabled(): bool
    {
        return (bool) db_config('config-mail-inbound.enabled', true);
    }

    public function allowedSenders(): array
    {
        return collect(db_config('config-mail-inbound.allowed_senders', []))
            ->map(fn ($row) => strtolower(trim((string) ($row['email'] ?? ''))))
            ->filter()
            ->values()
            ->all();
    }

    public function bugioRecipientCnpj(): ?string
    {
        return \App\Services\MailInbound\Support\DocumentIdentity::normalizeDigits(
            db_config('config-mail-inbound.bugio_recipient_cnpj')
        );
    }

    public function unidadeNegocio(): ?string
    {
        $value = trim((string) db_config('config-mail-inbound.unidade_negocio', ''));

        return $value !== '' ? $value : null;
    }
}
