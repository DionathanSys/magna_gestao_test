<?php

namespace App\Services\MailInbound;

use App\Models\IncomingEmail;
use App\Models\ReceivedFiscalDocument;
use Illuminate\Support\Facades\Log;
use Throwable;

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

        try {
            Log::info('Iniciando processamento fiscal do email ingerido', [
                'incoming_email_id' => $incomingEmail->id,
                'message_id' => $incomingEmail->message_id,
                'attachments_count' => $incomingEmail->attachments->count(),
            ]);

            $xmlAttachment = $incomingEmail->attachments->firstWhere('kind', 'xml');
            $pdfAttachment = $incomingEmail->attachments->firstWhere('kind', 'pdf');

            if (! $xmlAttachment) {
                $incomingEmail->update([
                    'status' => 'ignored',
                    'error_message' => 'Email sem XML para processamento fiscal.',
                ]);

                Log::warning('Email ingerido sem XML, processamento fiscal ignorado', [
                    'incoming_email_id' => $incomingEmail->id,
                ]);
                return;
            }

            $parsed = $this->parser->parse($xmlAttachment->disk, $xmlAttachment->path);
            $tipoDocumento = $this->typeResolver->resolve($parsed);
            $integrado = $this->integradoResolver->resolve($parsed);

            Log::info('XML fiscal parseado com sucesso', [
                'incoming_email_id' => $incomingEmail->id,
                'xml_attachment_id' => $xmlAttachment->id,
                'tipo_documento' => $tipoDocumento,
                'numero_nota' => $parsed['numero_nota'] ?? null,
                'chave_nfe' => $parsed['chave_nfe'] ?? null,
                'emitente_documento' => $parsed['emitente_documento'] ?? null,
                'destinatario_documento' => $parsed['destinatario_documento'] ?? null,
                'integrado_id' => $integrado?->id,
            ]);

            $referencedSaleNumber = $this->extractReferencedSaleNumber((string) ($parsed['inf_adic'] ?? ''));

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
                    'emitente_nome' => $parsed['emitente_nome'],
                    'emitente_documento' => $parsed['emitente_documento'],
                    'destinatario_nome' => $parsed['destinatario_nome'],
                    'destinatario_documento' => $parsed['destinatario_documento'],
                    'transportador_nome' => $parsed['transportador_nome'],
                    'transportador_documento' => $parsed['transportador_documento'],
                    'placa_transportador' => $parsed['placa_transportador'],
                    'peso_carga' => $parsed['peso_carga'],
                    'referenced_nfe_key' => $parsed['referenced_nfe_key'],
                    'referenced_sale_number' => $referencedSaleNumber,
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

            Log::info('Processamento fiscal finalizado', [
                'incoming_email_id' => $incomingEmail->id,
                'received_fiscal_document_id' => $document->id,
            ]);
        } catch (Throwable $exception) {
            $incomingEmail->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            Log::error('Falha no processamento fiscal do email ingerido', [
                'incoming_email_id' => $incomingEmail->id,
                'message_id' => $incomingEmail->message_id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    protected function extractReferencedSaleNumber(string $info): ?string
    {
        $patterns = [
            '/NOTA\s+FISCAL\s+N\.?\s*(\d{3,10})/iu',
            '/NOTA\s+FISCAL\s+N[O\.]?\s*(\d{3,10})/iu',
            '/NF\s+DE\s+VENDA[^\d]{0,20}(\d{3,10})/iu',
            '/N\.?\s*(\d{3,10})\s+DE\s+\d{2}\/\d{2}\/\d{2}/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $info, $matches)) {
                return $matches[1];
            }
        }

        if (preg_match('/\b(\d{3,10})\b/', $info, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
