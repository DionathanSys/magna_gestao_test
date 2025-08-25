<?php

namespace App\Filament\Resources\DocumentoFretes\Tables;

use App\Filament\Resources\DocumentoFretes\Actions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentoFretesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('veiculo.placa')
                    ->label('Placa')
                    ->searchable(isIndividual: true),
                TextColumn::make('numero_documento')
                    ->label('Nro. Documento')
                    ->searchable(isIndividual: true),
                TextColumn::make('documento_transporte')
                    ->label('Nro. Doc. Transp.')
                    ->searchable(isIndividual: true),
                TextColumn::make('tipo_documento')
                    ->label('Tipo Documento')
                    ->searchable(isIndividual: true),
                TextColumn::make('data_emissao')
                    ->label('Dt. Emissão')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('valor_total')
                    ->label('Vlr. Total')
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('valor_icms')
                    ->label('Vlr. ICMS')
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Criado Em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado Em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                Actions\ImportarDocumentoFreteAction::make(),
                CreateAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
