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
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;
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
                        ->columnSpan(4)
                        // ->afterStateUpdated(function (Set $set, ?\DateTime $state) {
                        //     if ($state) {
                        //         $set('vincular_periodo_registro', false);
                        //     }
                        // })
                        ->live(onBlur: true)
                        ->afterStateUpdatedJs(<<<'JS'
                            $set('vincular_periodo_registro', null)
                        JS),
                    Toggle::make('vincular_periodo_registro')
                        ->label('Vincular Período ao Registro')
                        ->helperText('Se ativado,será vinculado ao resultado correspondente à data dos registros selecionados.')
                        ->columnSpan(4)
                        // ->afterStateUpdated(function (Set $set, ?bool $state) {
                        //     if ($state) {
                        //         $set('data_inicio', null);
                        //     }
                        // })
                        ->live(onBlur: true)
                        ->afterStateUpdatedJs(<<<'JS'
                            $set('data_inicio', null)
                        JS),
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
