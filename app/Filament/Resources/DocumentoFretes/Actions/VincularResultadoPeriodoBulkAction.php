<?php

namespace App\Filament\Resources\DocumentoFretes\Actions;

use App\Jobs\VincularViagemDocumentoFrete;
use App\Models;
use App\Services\NotificacaoService as notify;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VincularResultadoPeriodoBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('vincular-resultado-periodo')
            ->label('Vincular Resultado Período')
            ->tooltip('Vincula ao Resultado Período baseado na data do registro')
            ->icon('heroicon-o-paper-clip')
            ->requiresConfirmation()
            ->action(function (Collection $records) {

                $vinculados = 0;
                $semResultadoPeriodo = 0;

                DB::beginTransaction();

                try {

                    $records->each(function (Models\DocumentoFrete $record) use (&$vinculados, &$erros, &$semResultadoPeriodo, &$semViagem) {

                        $resultadoPeriodoId = self::resultadoCorrespondente($record);

                        if (!$resultadoPeriodoId) {
                            Log::warning('Documento de frete sem resultado de período correspondente', [
                                'documento_frete_id'    => $record->id,
                                'veiculo_id'            => $record->veiculo_id,
                                'data_emissao'          => $record->data_emissao,
                            ]);
                            $semResultadoPeriodo++;
                            return;
                        }

                        $record->update([
                            'resultado_periodo_id' => $resultadoPeriodoId
                        ]);

                        $vinculados++;
                        return;

                    });

                    DB::commit();

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Erro ao vincular documentos de frete ao resultado de período', [
                        'error' => $e->getMessage(),
                    ]);
                    notify::error('Ocorreu um erro ao vincular os documentos de frete ao resultado de período.');
                    return;
                }

                notify::success("Vinculação concluída: {$vinculados} registros vinculados com sucesso, {$semResultadoPeriodo} registros sem correspondência.");
            })
            ->deselectRecordsAfterCompletion();
    }

    private static function resultadoCorrespondente(Models\DocumentoFrete $documentoFrete): ?int
    {
        return Models\ResultadoPeriodo::where('veiculo_id', $documentoFrete->veiculo_id)
            ->select('id')
            ->whereDate('data_inicio', '<=', $documentoFrete->data_emissao)
            ->whereDate('data_fim', '>=', $documentoFrete->data_emissao)
            ->first()?->id;
    }
}
