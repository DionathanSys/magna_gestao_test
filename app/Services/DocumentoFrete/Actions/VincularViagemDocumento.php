<?php

namespace App\Services\DocumentoFrete\Actions;

use App\Models;

class VincularViagemDocumento
{
    public function handle(Models\DocumentoFrete $documentoFrete, Models\Viagem $viagem): ?Models\DocumentoFrete
    {

        $this->validate($documentoFrete, $viagem);

        $documentoFrete->update([
            'viagem_id' => $viagem->id,
        ]);

        return $documentoFrete;
    }

    private function validate(Models\DocumentoFrete $documentoFrete, Models\Viagem $viagem): void
    {
        if ($documentoFrete->viagem_id) {
            throw new \Exception("DocumentoFrete já vinculado a uma viagem.");
        }

        if( $documentoFrete->documento_transporte !== $viagem->documento_transporte ) {
            throw new \Exception("DocumentoFrete e Viagem possuem documentos de transporte diferentes.");
        }
    }
}
