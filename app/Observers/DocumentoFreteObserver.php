<?php

namespace App\Observers;

use App\Models\DocumentoFrete;
use App\Models\Viagem;
use App\Services\Viagem\Actions\AtualizarResumoViagem;
use Illuminate\Support\Facades\Log;

class DocumentoFreteObserver
{
    /**
     * Handle the DocumentoFrete "created" event.
     * Se o documento já estiver vinculado a uma viagem com resultado, vincula o documento ao resultado.
     */
    public function created(DocumentoFrete $documento): void
    {
        if (! $documento->viagem_id) {
            return;
        }

        $viagem = Viagem::find($documento->viagem_id);

        if (! $viagem) {
            return;
        }

        if ($viagem->resultado_periodo_id) {
            $documento->updateQuietly([
                'resultado_periodo_id' => $viagem->resultado_periodo_id,
            ]);

            Log::info('DocumentoFrete vinculado ao ResultadoPeriodo via Observer (created).', [
                'documento_id' => $documento->id,
                'viagem_id' => $viagem->id,
                'resultado_periodo_id' => $viagem->resultado_periodo_id,
            ]);
        }

        (new AtualizarResumoViagem())->handle($viagem->id);
    }

    /**
     * Handle the DocumentoFrete "updated" event.
     * Quando o documento é vinculado a uma viagem (viagem_id alterado), vincula ao resultado da viagem.
     */
    public function updated(DocumentoFrete $documento): void
    {
        if ($documento->isDirty('viagem_id')) {
            // Se passou a ter viagem vinculada
            if ($documento->viagem_id) {
                $viagem = Viagem::find($documento->viagem_id);

                if ($viagem && $viagem->resultado_periodo_id) {
                    $documento->updateQuietly([
                        'resultado_periodo_id' => $viagem->resultado_periodo_id,
                    ]);

                    Log::info('DocumentoFrete vinculado ao ResultadoPeriodo via Observer (updated viagem_id).', [
                        'documento_id' => $documento->id,
                        'viagem_id' => $viagem->id,
                        'resultado_periodo_id' => $viagem->resultado_periodo_id,
                    ]);
                }
            }

            if($documento->getOriginal('viagem_id') && ! $documento->viagem_id) {
                $documento->updateQuietly([
                    'resultado_periodo_id' => null,
                ]);

                Log::info('DocumentoFrete desvinculado do ResultadoPeriodo via Observer (removed viagem_id).', [
                    'documento_id' => $documento->id,
                ]);
            }
        }

        if ($documento->viagem_id && $documento->isDirty(['viagem_id', 'numero_documento', 'valor_liquido', 'parceiro_destino'])) {
            (new AtualizarResumoViagem())->handle($documento->viagem_id);
        }

        if ($documento->getOriginal('viagem_id') && ($documento->getOriginal('viagem_id') !== $documento->viagem_id)) {
            (new AtualizarResumoViagem())->handle((int) $documento->getOriginal('viagem_id'));
        }
    }

    public function deleted(DocumentoFrete $documento): void
    {
        if ($documento->viagem_id) {
            (new AtualizarResumoViagem())->handle($documento->viagem_id);
        }
    }
}
