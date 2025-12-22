<?php

namespace App\Filament\Tables;

use App\Models\DocumentoFrete;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SelectDocumentoFrete
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => DocumentoFrete::query())
            ->columns([
                TextColumn::make('veiculo.id')
                    ->searchable(),
                TextColumn::make('parceiro_origem')
                    ->searchable(),
                TextColumn::make('parceiro_destino')
                    ->searchable(),
                TextColumn::make('numero_documento')
                    ->searchable(),
                TextColumn::make('documento_transporte')
                    ->searchable(),
                TextColumn::make('tipo_documento')
                    ->badge()
                    ->searchable(),
                TextColumn::make('data_emissao')
                    ->date()
                    ->sortable(),
                TextColumn::make('valor_total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('valor_icms')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('valor_liquido')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('municipio')
                    ->searchable(),
                TextColumn::make('estado')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('viagem.id')
                    ->searchable(),
                TextColumn::make('resultadoPeriodo.id')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
