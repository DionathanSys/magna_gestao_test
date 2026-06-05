<?php

namespace App\Services\MailInbound;

class FiscalDocumentTypeResolver
{
    /**
     * @param  array<string, mixed>  $parsedDocument
     */
    public function resolve(array $parsedDocument): string
    {
        $issuerDocument = app(MailInboundConfig::class)->issuerDocument();
        $saleRecipientDocument = app(MailInboundConfig::class)->saleRecipientDocument();

        if ($issuerDocument && ($parsedDocument['emitente_documento'] ?? null) !== $issuerDocument) {
            return 'unknown';
        }

        if ($saleRecipientDocument && ($parsedDocument['destinatario_documento'] ?? null) === $saleRecipientDocument) {
            return 'sale';
        }

        if (! empty($parsedDocument['destinatario_documento'])) {
            return 'remittance';
        }

        return 'unknown';
    }
}
