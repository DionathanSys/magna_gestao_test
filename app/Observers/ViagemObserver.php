<?php

namespace App\Observers;

use App\Models\Viagem;
use Illuminate\Support\Facades\Log;

class ViagemObserver
{
    /**
     * Handle the Viagem "updated" event.
     * Quando uma viagem é vinculada a um resultado período,
     * vincular todos os seus documentos frete ao mesmo resultado período
     */
    public function updated(Viagem $viagem): void
    {
        // Verifica se o campo resultado_periodo_id foi alterado
        if (!$viagem->isDirty('resultado_periodo_id')) {
            return;
        }

        // Se resultado_periodo_id foi preenchido (vinculação)
        if ($viagem->resultado_periodo_id && $viagem->getOriginal('resultado_periodo_id') !== $viagem->resultado_periodo_id) {
            $this->vincularDocumentos($viagem);
        }

        // Se resultado_periodo_id foi removido (desvinculação)
        if (!$viagem->resultado_periodo_id && $viagem->getOriginal('resultado_periodo_id')) {
            $this->desvincularDocumentos($viagem);
        }
    }

    /**
     * Handle the Viagem "deleting" event.
     * Quando uma viagem é deletada, desvincula todos os seus documentos frete
     */
    public function deleting(Viagem $viagem): void
    {
        $documentosAtualizados = $viagem->documentos()
            ->update([
                'viagem_id' => null,
                'resultado_periodo_id' => null,
                'updated_at' => now(),
            ]);

        if ($documentosAtualizados > 0) {
            Log::info('Documentos frete desvinculados da viagem via Observer (deleting).', [
                'viagem_id' => $viagem->id,
                'documentos_atualizados' => $documentosAtualizados,
            ]);
        }
    }

    /**
     * Vincular os documentos frete da viagem ao resultado período
     */
    private function vincularDocumentos(Viagem $viagem): void
    {
        $documentosAtualizados = $viagem->documentos()
            ->whereNull('resultado_periodo_id')
            ->update([
                'resultado_periodo_id' => $viagem->resultado_periodo_id,
                'updated_at' => now(),
            ]);

        if ($documentosAtualizados > 0) {
            Log::info('Documentos frete vinculados ao resultado período via Observer.', [
                'viagem_id' => $viagem->id,
                'resultado_periodo_id' => $viagem->resultado_periodo_id,
                'documentos_atualizados' => $documentosAtualizados,
            ]);
        }
    }

    /**
     * Desvincular os documentos frete da viagem do resultado período
     */
    private function desvincularDocumentos(Viagem $viagem): void
    {
        $resultadoPeriodoIdAnterior = $viagem->getOriginal('resultado_periodo_id');

        $documentosAtualizados = $viagem->documentos()
            ->where('resultado_periodo_id', $resultadoPeriodoIdAnterior)
            ->update([
                'resultado_periodo_id' => null,
                'updated_at' => now(),
            ]);

        if ($documentosAtualizados > 0) {
            Log::info('Documentos frete desvinculados do resultado período via Observer.', [
                'viagem_id' => $viagem->id,
                'resultado_periodo_id_anterior' => $resultadoPeriodoIdAnterior,
                'documentos_atualizados' => $documentosAtualizados,
            ]);
        }
    }
}
