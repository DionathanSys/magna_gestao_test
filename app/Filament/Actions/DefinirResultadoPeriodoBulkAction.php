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
use Filament\Support\Enums\Alignment;
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
            ->modalDescription('Defina o Resultado Período para os Documentos de Frete selecionados.')
            ->modalSubmitActionLabel('Sim, definir')
            ->modalAlignment(Alignment::Center)
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
                        ->columnSpan(4)
                        ->helperText('Selecione a data de início do período que deseja vincular aos registros selecionados.')
                        ->live()
                        ->afterStateUpdatedJs(<<<'JS'
                            if($state) {
                                $set('vincular_periodo_registro', false)
                            }
                        JS),
                    Toggle::make('vincular_periodo_registro')
                        ->label('Vincular Período ao Registro')
                        ->helperText('Se ativado,será vinculado ao resultado correspondente à data dos registros selecionados.')
                        ->columnSpan(4)
                        ->columnStart(1)
                        ->live()
                        ->afterStateUpdatedJs(<<<'JS'
                            if($state) {
                                $set('data_inicio', null)
                            }
                        JS),
                ])
            ])
            ->action(function (Collection $records, array $data) {
                $recordsUpdated = 0;
                $recordsFailed = 0;
                $records->each(function ($record) use (&$recordsUpdated, &$recordsFailed, $data) {
                    try {
                        $resultadoPeriodoId = null;

                        if ($data['vincular_periodo_registro']) {
                            $resultadoPeriodoId = $this->getResultadoPeriodoIdByRegistro($record);
                        } else {
                            $resultadoPeriodoId = $this->getResultadoPeriodoIdByData($data['data_inicio'], $record->veiculo_id);
                        }

                        if ($resultadoPeriodoId) {
                            $record->resultado_periodo_id = $resultadoPeriodoId;
                            $record->save();
                            $recordsUpdated++;
                        }
                    } catch (\Exception $e) {
                        $recordsFailed++;
                        Log::error('Erro ao definir Resultado Período no Bulk Action.', [
                            'metodo' => __METHOD__,
                            'record_id' => $record->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                });
            })
            ->deselectRecordsAfterCompletion();
    }

    private function getResultadoPeriodoIdByData(?string $data, int $veiculoId): ?int
    {
        if (!$data) {
            return null;
        }

        $resultadoPeriodo = ResultadoPeriodo::query()
            ->where('veiculo_id', $veiculoId)
            ->whereDate('data_inicio', '<=', $data)
            ->whereDate('data_fim', '>=', $data)
            ->first();

        return $resultadoPeriodo?->id;
    }

    private function getResultadoPeriodoIdByRegistro($record): ?int
    {
        $dataAbastecimento  = $record->data_referencia;
        $veiculoId          = $record->veiculo_id;

        $resultadoPeriodo = ResultadoPeriodo::query()
            ->where('veiculo_id', $veiculoId)
            ->whereDate('data_inicio', '<=', $dataAbastecimento)
            ->whereDate('data_fim', '>=', $dataAbastecimento)
            ->first();

        return $resultadoPeriodo?->id;
    }

}
