<?php

namespace App\Filament\Actions;

use App\Models\ResultadoPeriodo;
use App\Services\Import\AbastecimentoImportService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use App\Services\NotificacaoService as notify;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class DefinirResultadoPeriodoBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('definir_resultado_periodo')
            ->label('Definir Resultado Período')
            ->icon(Heroicon::Link)
            ->color('warning')
            ->modalHeading('Definir Resultado Período')
            ->modalDescription('Defina o Resultado Período para os Documentos de Frete selecionados. Apenas Documentos de Frete do mesmo veículo serão atualizados.')
            ->modalSubmitActionLabel('Sim, definir')
            ->schema([
                Grid::make([
                    'default' => 1,
                    'sm' => 2,
                    'md' => 3,
                    'lg' => 4,
                    'xl' => 6,
                    '2xl' => 8,
                ])->schema([
                    DatePicker::make('data_inicio')
                        ->label('Data Início')
                        ->required()
                        ->columnSpan(4),
                ])
            ])
            ->action(function (Collection $records, array $data) {
                $veiculoId = $records->first()->veiculo_id;
                $records = $records->filter(function ($record) use ($veiculoId) {
                    return $record->veiculo_id === $veiculoId;
                });
                dd($records, $data);
            })
            ->deselectRecordsAfterCompletion();
    }
}
