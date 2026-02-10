<?php

namespace App\Filament\Resources\Viagems\Actions;

use App\Jobs\VincularViagemResultadoPeriodoJob;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Collection;
use Livewire\Component;

class VincularViagemResultadoPeriodoBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('vincular-resultado-periodo')
            ->label('Vincular a Resultado Período')
            ->icon('heroicon-o-link')
            ->color('success')
            ->schema([
                DatePicker::make('data_inicio')
                    ->label('Data de Início')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->helperText('Selecione a data de início do período'),
                
                DatePicker::make('data_fim')
                    ->label('Data de Fim')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->helperText('Selecione a data de fim do período')
                    ->after('data_inicio'),
            ])
            ->action(function (Collection $records, array $data) {
                $dataInicio = $data['data_inicio'];
                $dataFim = $data['data_fim'];

                $records->each(function ($viagem) use ($dataInicio, $dataFim) {
                    VincularViagemResultadoPeriodoJob::dispatch(
                        $viagem->id,
                        $dataInicio,
                        $dataFim
                    );
                });
            })
            ->after(fn(Component $livewire) => $livewire->js(<<<'JS'
                let segundosRestantes = 15;
                
                // Cria e exibe o alerta
                const alerta = document.createElement('div');
                alerta.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 16px 24px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 9999; font-weight: 600;';
                alerta.innerHTML = `Vinculando viagens... Atualizando em <span id="countdown">${segundosRestantes}</span>s...`;
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
                
                // Executa o refresh e remove o alerta após 15 segundos
                setTimeout(() => {
                    alerta.remove();
                    $wire.$refresh();
                }, 15000);
            JS))
            ->deselectRecordsAfterCompletion();
    }
}
