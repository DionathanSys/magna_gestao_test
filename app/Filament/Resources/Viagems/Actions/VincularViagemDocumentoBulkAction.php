<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Jobs\VincularViagemDocumentoFrete;
use App\Jobs\VincularViagensBatch;
use App\Models;
use App\Services;
use App\Services\NotificacaoService as notify;
use Filament\Actions\BulkAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class VincularViagemDocumentoBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('vincular-documento')
            ->label('Vincular Documento')
            ->icon('heroicon-o-paper-clip')
            ->action(function (Collection $records) {
                $records->chunk(250)->each(function (Collection $chunk) {
                    VincularViagensBatch::dispatch($chunk);
                });
            })
            ->after(fn(Component $livewire) => $livewire->js(<<<'JS'
                            let segundosRestantes = 20;
                            
                            // Cria e exibe o alerta
                            const alerta = document.createElement('div');
                            alerta.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #3b82f6; color: white; padding: 16px 24px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 9999; font-weight: 600;';
                            alerta.innerHTML = `Atualizando em <span id="countdown">${segundosRestantes}</span>s...`;
                            document.body.appendChild(alerta);
                            
                            // Atualiza o contador a cada segundo
                            const intervalo = setInterval(() => {
                                segundosRestantes--;
                                const countdown = document.getElementById('countdown');
                                if (countdown) {
                                    countdown.textContent = segundosRestantes;
                                }
                                
                                if (segundosRestantes <= 0) {
                                    clearInterval(intervalo);
                                }
                            }, 1000);
                            
                            // Executa o refresh e remove o alerta após 20 segundos
                            setTimeout(() => {
                                alerta.remove();
                                $wire.$refresh();
                            }, 20000);
                        JS))
            ->deselectRecordsAfterCompletion();
    }
}
