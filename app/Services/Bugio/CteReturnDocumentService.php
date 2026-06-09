<?php

namespace App\Services\Bugio;

use App\Enum\Frete\TipoDocumentoEnum;
use App\Models\CteEmailRequest;
use App\Models\IncomingEmailAttachment;
use App\Services\DocumentoFrete\DocumentoFreteService;
use Illuminate\Support\Facades\Log;

class CteReturnDocumentService
{
    public function __construct(
        protected CteXmlParser $parser,
        protected CteEmailRequestService $requestService,
    ) {}

    public function processAttachment(CteEmailRequest $request, IncomingEmailAttachment $attachment): void
    {
        $metadata = $attachment->metadata ?? [];
        $metadata['cte_email_request_id'] = $request->id;

        try {
            if ($attachment->kind !== 'xml') {
                $attachment->update([
                    'status' => 'processed',
                    'metadata' => [
                        ...$metadata,
                        'cte_return' => 'ignored_non_xml',
                        'processed_at' => now()->toISOString(),
                    ],
                ]);

                return;
            }

            $this->requestService->markProcessing($request);

            $parsed = $this->parser->parse($attachment->disk, $attachment->path);
            $viagem = $request->viagem()->with('veiculo')->firstOrFail();

            $documentoFrete = [
                'veiculo_id' => $viagem->veiculo_id,
                'parceiro_destino' => $request->integrado?->nome
                    ?? $parsed['tomador_nome']
                    ?? $parsed['destinatario_nome']
                    ?? 'BUGIO',
                'parceiro_origem' => $parsed['emitente_nome']
                    ?? $parsed['remetente_nome']
                    ?? 'EMISSOR CT-E',
                'numero_documento' => $parsed['numero_cte'] ?? $parsed['chave_cte'],
                'documento_transporte' => $request->documento_transporte,
                'data_emissao' => $parsed['emitido_em']?->format('Y-m-d H:i:s') ?? now()->format('Y-m-d H:i:s'),
                'valor_total' => $parsed['valor_total'] > 0 ? $parsed['valor_total'] : $parsed['valor_receber'],
                'valor_icms' => $parsed['valor_icms'] ?? 0,
                'tipo_documento' => ($parsed['tipo_documento'] ?? TipoDocumentoEnum::CTE->value) === TipoDocumentoEnum::CTE_COMPLEMENTO->value
                    ? TipoDocumentoEnum::CTE_COMPLEMENTO
                    : TipoDocumentoEnum::CTE,
                'viagem_id' => $request->viagem_id,
            ];

            Log::info('Criando DocumentoFrete a partir de retorno de CT-e', [
                'cte_email_request_id' => $request->id,
                'incoming_email_attachment_id' => $attachment->id,
                'documento_transporte' => $request->documento_transporte,
                'numero_documento' => $documentoFrete['numero_documento'],
            ]);

            (new DocumentoFreteService)->criarDocumentoFrete($documentoFrete);

            $attachment->update([
                'status' => 'processed',
                'metadata' => [
                    ...$metadata,
                    'cte_return' => 'document_created',
                    'documento_transporte' => $request->documento_transporte,
                    'numero_documento' => $documentoFrete['numero_documento'],
                    'processed_at' => now()->toISOString(),
                ],
            ]);

            $this->requestService->markCompleted($request->fresh());
        } catch (\Throwable $exception) {
            $attachment->update([
                'status' => 'failed',
                'metadata' => [
                    ...$metadata,
                    'cte_return' => 'failed',
                    'error_message' => $exception->getMessage(),
                    'processed_at' => now()->toISOString(),
                ],
            ]);

            $this->requestService->markFailed($request, $exception->getMessage());

            throw $exception;
        }
    }
}
