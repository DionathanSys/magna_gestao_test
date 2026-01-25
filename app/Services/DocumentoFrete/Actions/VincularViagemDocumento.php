<?php

namespace App\Services\DocumentoFrete\Actions;

use App\Models;
use App\Models\DocumentoFrete;
use Illuminate\Support\Facades\Log;

class VincularViagemDocumento
{
    public function handle(int $documentoTransporte, int $viagemId): int
    {
        try {
            Log::info('Iniciando ação de vinculação do documento de frete à viagem', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'documento_transporte' => $documentoTransporte,
                'viagem_id' => $viagemId,
            ]);
            $updated = DocumentoFrete::query()
                ->where('documento_transporte', $documentoTransporte)
                ->update([
                    'viagem_id' => $viagemId,
                ]);

            if ($updated === 0) {
                Log::warning('Nenhum documento de frete encontrado', [
                    'documento_transporte' => $documentoTransporte,
                ]);
            }

            return $updated;
        } catch (\Exception $e) {
            Log::error('Erro ao iniciar ação de vinculação do documento de frete à viagem', [
                'metodo' => __METHOD__ . '@' . __LINE__,
                'documento_transporte' => $documentoTransporte,
                'viagem_id' => $viagemId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }
}
