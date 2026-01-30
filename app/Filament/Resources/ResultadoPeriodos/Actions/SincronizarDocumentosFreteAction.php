<?php

namespace App\Filament\Resources\ResultadoPeriodos\Actions;

use Filament\Actions\BulkAction;
use App\Models;
use App\Services\NotificacaoService as notify;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SincronizarDocumentosFreteAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('sincronizar_documentos_frete')
            ->label('Sincronizar Documentos de Frete')
            ->icon(Heroicon::ArrowPath)
            ->color('info')
            ->requiresConfirmation()
            ->modalDescription('Esta ação irá desvincular todos os documentos de frete atuais e vincular novamente baseado nas viagens do período. Deseja continuar?')
            ->action(function (Collection $records) {
                $totalDocumentos = 0;
                
                $records->each(function (Models\ResultadoPeriodo $resultadoPeriodo) use (&$totalDocumentos) {
                    Log::info('Sincronizando documentos de frete para Resultado Período', [
                        'resultado_periodo_id' => $resultadoPeriodo->id,
                        'veiculo' => $resultadoPeriodo->veiculo->placa ?? 'N/A',
                    ]);
                    
                    // 1. Remove todos os documentos já vinculados ao resultado período
                    Models\DocumentoFrete::where('resultado_periodo_id', $resultadoPeriodo->id)
                        ->update(['resultado_periodo_id' => null]);
                    
                    Log::debug('Documentos desvinculados do Resultado Período', [
                        'resultado_periodo_id' => $resultadoPeriodo->id,
                    ]);
                    
                    // 2. Busca todas as viagens vinculadas ao resultado período
                    $viagens = Models\Viagem::where('resultado_periodo_id', $resultadoPeriodo->id)->get();
                    
                    Log::debug('Viagens encontradas para o Resultado Período', [
                        'resultado_periodo_id' => $resultadoPeriodo->id,
                        'quantidade_viagens' => $viagens->count(),
                    ]);
                    
                    // 3. Para cada viagem, busca os documentos e vincula ao resultado período
                    $viagens->each(function (Models\Viagem $viagem) use ($resultadoPeriodo, &$totalDocumentos) {
                        $documentos = Models\DocumentoFrete::where('viagem_id', $viagem->id)->get();
                        
                        Log::debug('Documentos encontrados para a viagem', [
                            'viagem_id' => $viagem->id,
                            'quantidade_documentos' => $documentos->count(),
                        ]);
                        
                        $documentos->each(function (Models\DocumentoFrete $documento) use ($resultadoPeriodo, &$totalDocumentos) {
                            $documento->update(['resultado_periodo_id' => $resultadoPeriodo->id]);
                            $totalDocumentos++;
                            
                            Log::debug('Documento vinculado ao Resultado Período', [
                                'documento_id' => $documento->id,
                                'numero_documento' => $documento->numero_documento,
                                'resultado_periodo_id' => $resultadoPeriodo->id,
                            ]);
                        });
                    });
                    
                    Log::info('Sincronização concluída para Resultado Período', [
                        'resultado_periodo_id' => $resultadoPeriodo->id,
                        'total_viagens' => $viagens->count(),
                    ]);
                });
                
                notify::success(mensagem: "Sincronização concluída! {$totalDocumentos} documentos vinculados.");
            });
    }
}
