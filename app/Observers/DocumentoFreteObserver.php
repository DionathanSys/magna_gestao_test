<?php

namespace App\Observers;

use App\Models\DocumentoFrete;
use App\Models\Viagem;
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

        $documentosTransporte = array_unique(array_filter([
            $documentoFrete->documento_transporte,
            $documentoFrete->getOriginal('documento_transporte'),
        ]));

        if ($documentosTransporte !== []) {
            $ids = array_merge(
                $ids,
                Viagem::query()
                    ->whereIn('documento_transporte', $documentosTransporte)
                    ->pluck('id')
                    ->all()
            );
        }

        $ids = array_unique(array_filter($ids));

        foreach ($ids as $id) {
            app(AtualizarResumoViagem::class)->handle((int) $id);
        }
    }
}
