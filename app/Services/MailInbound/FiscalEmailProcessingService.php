<?php

namespace App\Services\MailInbound;

use App\Models\IncomingEmail;
use App\Models\ReceivedFiscalDocument;
use Illuminate\Support\Facades\Log;

class FiscalEmailProcessingService
{
    public function __construct(
        protected NfeXmlParser $parser,
        protected FiscalDocumentTypeResolver $typeResolver,
        protected IntegradoResolver $integradoResolver,
        protected ShipmentDocumentMatcher $matcher,
    ) {
    }

    public function process(int $incomingEmailId): void
    {
        $incomingEmail = IncomingEmail::query()->with('attachments')->findOrFail($incomingEmailId);

        $xmlAttachment = $incomingEmail->attachments->firstWhere('kind', 'xml');
        $pdfAttachment = $incomingEmail->attachments->firstWhere('kind', 'pdf');

        if (! $xmlAttachment) {
            $incomingEmail->update([
                'status' => 'ignored',
                'error_message' => 'Email sem XML para processamento fiscal.',
            ]);
            return;
        }

        $parsed = $this->parser->parse($xmlAttachment->disk, $xmlAttachment->path);
        $tipoDocumento = $this->typeResolver->resolve($parsed);
        $integrado = $this->integradoResolver->resolve($parsed);

        preg_match('/\b(\d{1,9})\b/', (string) ($parsed['inf_adic'] ?? ''), $saleNumberMatch);

        $document = ReceivedFiscalDocument::query()->updateOrCreate(
            [
                'chave_nfe' => $parsed['chave_nfe'],
            ],
            [
                'incoming_email_id' => $incomingEmail->id,
                'xml_attachment_id' => $xmlAttachment->id,
                'pdf_attachment_id' => $pdfAttachment?->id,
                'tipo_documento' => $tipoDocumento,
                'numero_nota' => $parsed['numero_nota'],
                'serie' => $parsed['serie'],
                'emitido_em' => $parsed['emitido_em'],
                'destinatario_nome' => $parsed['destinatario_nome'],
                'destinatario_cnpj' => $parsed['destinatario_cnpj'],
                'transportador_nome' => $parsed['transportador_nome'],
                'transportador_cnpj' => $parsed['transportador_cnpj'],
                'placa_transportador' => $parsed['placa_transportador'],
                'peso_carga' => $parsed['peso_carga'],
                'referenced_nfe_key' => $parsed['referenced_nfe_key'],
                'referenced_sale_number' => $saleNumberMatch[1] ?? null,
                'integrado_id' => $integrado?->id,
                'status' => 'parsed',
                'payload' => $parsed,
            ]
        );

        $incomingEmail->update([
            'status' => 'processed',
            'error_message' => null,
        ]);

        $this->matcher->match($document);
    }
}
