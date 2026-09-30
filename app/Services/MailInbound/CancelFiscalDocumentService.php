<?php

namespace App\Services\MailInbound;

use App\Models\CteEmailRequest;
use App\Models\ReceivedFiscalDocument;
use App\Models\ShipmentDocumentGroup;
use App\Models\Viagem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CancelFiscalDocumentService
{
    /**
     * @return array{groups: int, trips: int, resolution: string}
     */
    public function handle(ReceivedFiscalDocument $document, string $resolution, string $reason, ?int $userId): array
    {
        return DB::transaction(function () use ($document, $resolution, $reason, $userId): array {
            $document->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $userId,
                'cancellation_reason' => $reason,
                'cancellation_resolution' => $resolution,
            ]);

            $groups = ShipmentDocumentGroup::query()
                ->where('sale_document_id', $document->id)
                ->orWhere('remittance_document_id', $document->id)
                ->lockForUpdate()
                ->get();

            $trips = $groups->pluck('viagem_id')
                ->filter()
                ->unique()
                ->map(fn (int $id) => Viagem::query()->lockForUpdate()->find($id))
                ->filter();

            if ($resolution === 'delete_trip') {
                $this->deleteTrips($trips);
            } else {
                $this->cancelPendingCteRequests($trips);
                $this->blockAndIgnoreTrips($trips, $document);
            }

            $groups->each(function (ShipmentDocumentGroup $group) use ($resolution): void {
                $group->update([
                    'viagem_id' => $resolution === 'delete_trip' ? null : $group->viagem_id,
                    'status' => 'cancelled',
                ]);
            });

            return [
                'groups' => $groups->count(),
                'trips' => $trips->count(),
                'resolution' => $resolution,
            ];
        });
    }

    /** @param Collection<int, Viagem> $trips */
    protected function deleteTrips(Collection $trips): void
    {
        $protectedTrips = $trips->filter(fn (Viagem $trip): bool => $trip->documentos()->exists() || $trip->cteEmailRequests()->exists());

        if ($protectedTrips->isNotEmpty()) {
            throw new \DomainException('Não é possível excluir viagem com documento de frete ou solicitação de CT-e. Use bloquear e ignorar para preservar o histórico.');
        }

        $trips->each(function (Viagem $trip): void {
            $trip->delete();
        });
    }

    /** @param Collection<int, Viagem> $trips */
    protected function blockAndIgnoreTrips(Collection $trips, ReceivedFiscalDocument $document): void
    {
        $message = "NF-e {$document->numero_nota} cancelada";

        $trips->each(function (Viagem $trip) use ($message): void {
            $pendencias = collect($trip->pendencias ?? [])
                ->push($message)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $trip->update([
                'ignorar' => true,
                'possui_pendencia' => true,
                'pendencias' => $pendencias,
            ]);
        });
    }

    /** @param Collection<int, Viagem> $trips */
    protected function cancelPendingCteRequests(Collection $trips): void
    {
        CteEmailRequest::query()
            ->whereIn('viagem_id', $trips->pluck('id')->all())
            ->where('status', 'pending_send')
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => 'Cancelada porque uma NF-e vinculada foi cancelada.',
            ]);
    }
}
