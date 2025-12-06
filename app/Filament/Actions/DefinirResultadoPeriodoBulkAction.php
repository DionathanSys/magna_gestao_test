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
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class DefinirResultadoPeriodoBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('definir_resultado_periodo')
            ->label('Definir Resultado Período')
            ->icon('heroicon-o-x-circle')
            ->color('warning')
            // ->requiresConfirmation()
            ->modalHeading('Definir Registros')
            ->modalDescription('Tem certeza que deseja definir os registros selecionados para o resultado do período?')
            ->modalSubmitActionLabel('Sim, definir')
            ->schema([
                Select::make('resultado_periodo_id')
                        ->label('Resultado Período')
                        ->getSearchResultsUsing(fn(string $search): array => ResultadoPeriodo::query()
                            ->where('title', 'like', "%{$search}%")
                            ->limit(50)
                            ->pluck('title', 'id')
                            ->all())
                        ->getOptionLabelUsing(fn($value): ?string => ResultadoPeriodo::find($value)?->title)
                        ->searchable()
                        ->required(),
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
