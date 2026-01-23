<?php

namespace App\Services\ResultadoPeriodo;

use App\Models;
use Illuminate\Support\Facades\Log;

class ResultadoPeriodoService
{
    public function importarRegistros(int $resultadoPeriodoId)
    {
        try {

            $importarAbastecimentosAction = new Actions\ImportarAbastecimentos($resultadoPeriodoId);
            $importarAbastecimentosAction->handle();

            Log::info('Importação de abastecimentos concluída com sucesso.', [
                'metodo' => __METHOD__,
            ]);

            $importarDocumentosFreteAction = new Actions\ImportarDocumentosFrete($resultadoPeriodoId);
            $importarDocumentosFreteAction->handle();

            Log::info('Importação de documentos de frete concluída com sucesso.', [
                'metodo' => __METHOD__,
            ]);

            $importarViagensAction = new Actions\ImportarViagens($resultadoPeriodoId);
            $importarViagensAction->handle();

            Log::info('Importação de viagens concluída com sucesso.', [
                'metodo' => __METHOD__,
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao importar registros para o resultado do período.', [
                'metodo' => __METHOD__,
                'resultado_periodo_id' => $resultadoPeriodoId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function importarViagem()
    {

    }


}