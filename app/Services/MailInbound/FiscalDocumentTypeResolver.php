<?php

namespace App\Services\MailInbound;

class FiscalDocumentTypeResolver
{
    /**
     * @param  array<string, mixed>  $parsedDocument
     */
    public function resolve(array $parsedDocument): string
    {
        $bugioCnpj = app(MailInboundConfig::class)->bugioRecipientCnpj();

        if ($bugioCnpj && ($parsedDocument['destinatario_cnpj'] ?? null) === $bugioCnpj) {
            return 'sale';
        }

        if (! empty($parsedDocument['destinatario_cnpj'])) {
            return 'remittance';
        }

        return 'unknown';
    }
}
