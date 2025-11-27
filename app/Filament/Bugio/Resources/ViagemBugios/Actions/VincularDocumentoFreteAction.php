<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Actions;

use App\Models\DocumentoFrete;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;

class VincularDocumentoFreteAction
{
    public static function make(): Action
    {
        return Action::make('vincular_documento_frete')
            ->label('Vincular Documento de Frete')
            ->schema([
                Select::make('documento_frete_id')
                    ->label('Documento de Frete')
                    ->searchable()
                    ->preload()
                    ->getSearchResultsUsing(fn(string $search): array => DocumentoFrete::query()
                        // ->where('parceiro_origem', "BUGIO AGROPECUARIA LTDA")
                        ->where('parceiro_destino', 'like', "%{$search}%")
                        ->limit(50)
                        ->pluck('numero_documento', 'id')
                        ->all())
                    ->getOptionLabelUsing(fn($value): ?string => DocumentoFrete::find($value)?->descricao)
                    ->getOptionLabelFromRecordUsing(fn(DocumentoFrete $record) => $record->descricao)
                    ->required(),
            ])
            ->action(function (array $data, $record) {
                dd($data, $record);
            });
    }
}
