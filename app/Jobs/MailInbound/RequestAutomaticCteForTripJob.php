<?php

namespace App\Jobs\MailInbound;

use App\Models\CteEmailRequest;
use App\Models\ShipmentDocumentGroup;
use App\Services\Bugio\CteEmailQueueService;
use App\Services\Telegram\ViagemBugioTelegramAlertService;
use App\Services\Viagem\Actions\SolicitarCteBugioFromViagem;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class RequestAutomaticCteForTripJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $shipmentDocumentGroupId)
    {
        $this->onQueue((string) config('mail-inbound.queue.trip'));
    }

    public function handle(
        SolicitarCteBugioFromViagem $cteService,
        CteEmailQueueService $queueService,
        ViagemBugioTelegramAlertService $alertService,
    ): void {
        $group = ShipmentDocumentGroup::query()
            ->with([
                'viagem.veiculo',
                'viagem.cargas.integrado',
                'viagem.attachments.incomingEmailAttachment',
                'viagem.attachments.receivedFiscalDocument',
                'integrado',
                'saleDocument',
                'remittanceDocument.integrado',
                'remittanceDocument',
            ])
            ->findOrFail($this->shipmentDocumentGroupId);

        $viagem = $group->viagem;

        if (! $viagem) {
            return;
        }

        $request = null;
        $reason = null;

        if ($group->status === 'cancelled') {
            $reason = 'o grupo de documentos está cancelado';
        } elseif ($viagem->ignorar) {
            $reason = 'a viagem está marcada para ignorar';
        } else {
            $integrado = $group->remittanceDocument?->integrado
                ?? $group->integrado
                ?? $viagem->cargas->map(fn ($carga) => $carga->integrado)->filter()->first();

            if (! $integrado) {
                $reason = 'não foi possível identificar o integrado';
            } elseif ($cteService->isGuatambuMunicipio($integrado->municipio)) {
                $reason = 'o município do integrado foi identificado como Guatambu';
            } else {
                try {
                    $request = Cache::lock('cte:auto:shipment-group:'.$group->id, 30)->block(10, function () use ($group, $viagem, $integrado, $cteService, $queueService): ?CteEmailRequest {
                        $existingRequest = CteEmailRequest::query()
                            ->where('viagem_id', $viagem->id)
                            ->where('status', '!=', 'cancelled')
                            ->latest('id')
                            ->first();

                        if ($existingRequest) {
                            return $existingRequest;
                        }

                        $automaticRequest = CteEmailRequest::query()
                            ->where('shipment_document_group_id', $group->id)
                            ->latest('id')
                            ->first();

                        if ($automaticRequest) {
                            return $automaticRequest;
                        }

                        $motoristaCpf = trim((string) data_get(
                            $viagem->veiculo?->informacoes_complementares,
                            'motorista_padrao_cte_cpf',
                        ));

                        $dataCompetencia = $viagem->data_competencia
                            ?: $group->remittanceDocument?->emitido_em
                            ?: $group->saleDocument?->emitido_em
                            ?: now();

                        $prepared = $cteService->preparePayload($viagem, [
                            'integrado_id' => $integrado->id,
                            'motorista' => $motoristaCpf,
                            'tipo_documento' => 'CTe',
                            'km_rota' => (float) ($integrado->km_rota ?? 0),
                            'data_competencia' => Carbon::parse($dataCompetencia)->toDateString(),
                            'cte_retroativo' => true,
                        ]);

                        return $queueService->enqueue(
                            $prepared['payload'],
                            CteEmailRequest::ORIGIN_MAIL_INBOUND,
                            $group->id,
                        );
                    });

                    if ($request && $request->origin !== CteEmailRequest::ORIGIN_MAIL_INBOUND) {
                        $reason = 'já existe uma solicitação manual para esta viagem';
                    }
                } catch (\InvalidArgumentException|\DomainException $exception) {
                    $reason = $exception->getMessage();
                }
            }
        }

        $alertService->dispatch($viagem, $request, $reason);

        Log::info('Processamento automático de CTe da viagem finalizado', [
            'shipment_document_group_id' => $group->id,
            'viagem_id' => $viagem->id,
            'cte_email_request_id' => $request?->id,
            'reason' => $reason,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $group = ShipmentDocumentGroup::query()->with('viagem')->find($this->shipmentDocumentGroupId);
        $request = CteEmailRequest::query()
            ->where('shipment_document_group_id', $this->shipmentDocumentGroupId)
            ->latest('id')
            ->first();

        if ($group?->viagem) {
            app(ViagemBugioTelegramAlertService::class)->dispatch(
                $group->viagem,
                $request,
                'falha ao preparar a solicitação automática: '.$exception->getMessage(),
            );
        }

        Log::error('Falha definitiva ao preparar solicitação automática de CTe', [
            'shipment_document_group_id' => $this->shipmentDocumentGroupId,
            'error' => $exception->getMessage(),
        ]);
    }
}
