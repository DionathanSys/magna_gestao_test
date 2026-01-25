<?php

namespace App\Services\DocumentoFrete\Actions;

use App\Models;
use App\Models\DocumentoFrete;
use Illuminate\Support\Facades\Log;

class VincularViagemDocumento
{
    public function handle(int $documentoTransporte, int $viagemId): int
    {
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
    }
}
