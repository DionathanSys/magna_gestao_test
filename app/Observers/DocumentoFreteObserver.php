<?php

namespace App\Observers;

use App\Models\DocumentoFrete;
use App\Services\Viagem\Actions\AtualizarResumoViagem;

class DocumentoFreteObserver
{
    public function saved(DocumentoFrete $documentoFrete): void
    {
        $this->atualizarViagensRelacionadas($documentoFrete);
    }

    public function deleted(DocumentoFrete $documentoFrete): void
    {
        $this->atualizarViagensRelacionadas($documentoFrete);
    }

    private function atualizarViagensRelacionadas(DocumentoFrete $documentoFrete): void
    {
        $viagemIdAtual = $documentoFrete->viagem_id;
        $viagemIdAnterior = $documentoFrete->getOriginal('viagem_id');

        $ids = array_unique(array_filter([$viagemIdAtual, $viagemIdAnterior]));

        foreach ($ids as $id) {
            app(AtualizarResumoViagem::class)->handle((int) $id);
        }
    }
}
