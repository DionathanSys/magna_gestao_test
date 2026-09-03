<?php

namespace App\Filament\Resources\CteEmailRequests\RelationManagers;

use App\Filament\Resources\Viagems\ViagemResource;
use App\Models\DocumentoFrete;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentosFreteRelationManager extends RelationManager
{
    protected static string $relationship = 'documentosFrete';

    protected static ?string $title = 'Resultado CTe';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('numero_documento')->label('Numero CTe')->searchable(),
                TextColumn::make('tipo_documento')->label('Tipo')->badge()
                    ->formatStateUsing(fn ($state): string => $state?->value ?? (string) $state),
                TextColumn::make('documento_transporte')->label('Doc. Transporte')->searchable(),
                TextColumn::make('data_emissao')->label('Emitido em')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('valor_total')->label('Valor total')->money('BRL')->sortable(),
                TextColumn::make('viagem.numero_viagem')
                    ->label('Viagem')
                    ->url(fn (DocumentoFrete $record): ?string => $record->viagem_id
                        ? ViagemResource::getUrl('view', ['record' => $record->viagem_id])
                        : null)
                    ->openUrlInNewTab()
                    ->placeholder('-'),
            ]);
    }
}
