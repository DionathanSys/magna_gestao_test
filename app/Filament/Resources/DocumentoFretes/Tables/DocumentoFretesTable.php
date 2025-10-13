<?php

namespace App\Filament\Resources\DocumentoFretes\Tables;

use App\Filament\Resources\DocumentoFretes\Actions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class DocumentoFretesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('veiculo.placa')
                    ->label('Placa')
                    ->width('1%')
                    ->searchable(isIndividual: true),
                TextColumn::make('numero_documento')
                    ->label('Nro. Documento')
                    ->width('1%')
                    ->searchable(isIndividual: true),
                TextColumn::make('documento_transporte')
                    ->label('Nro. Doc. Transp.')
                    ->width('1%')
                    ->searchable(isIndividual: true),
                TextColumn::make('tipo_documento')
                    ->label('Tipo Documento')
                    ->width('1%')
                    ->searchable(isIndividual: true),
                TextColumn::make('viagem_id')
                    ->label("viagem ID"),
                TextColumn::make('data_emissao')
                    ->label('Dt. Emissão')
                    ->width('1%')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('valor_total')
                    ->label('Vlr. Total')
                    ->width('1%')
                    ->money('BRL')
                    ->sortable()
                    ->summarize(Sum::make()->money('BRL', 100)),
                TextColumn::make('valor_icms')
                    ->label('Vlr. ICMS')
                    ->width('1%')
                    ->money('BRL')
                    ->sortable()
                    ->summarize(Sum::make()->money('BRL', 100)),
                TextColumn::make('parceiro_origem')
                    ->label('Parceiro Origem')
                    ->width('1%')
                    ->searchable(isIndividual: true)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('parceiro_destino')
                    ->label('Parceiro Destino')
                    ->width('1%')
                    ->searchable(isIndividual: true)
                    ->toggleable(isToggledHiddenByDefault: true),
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
                SelectFilter::make('veiculo_id')
                    ->label('Veículo')
                    ->relationship('veiculo', 'placa')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                DateRangeFilter::make('data_emissao')
                    ->label('Dt. Emissão')
                    ->alwaysShowCalendar(),
                Filter::make('sem_vinculo_viagem')
                    ->label('Sem Viagem')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->whereNull('viagem_id')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([])
            ->toolbarActions([
                BulkActionGroup::make([
                    Actions\ImportarDocumentoFreteAction::make(),
                    Actions\VincularViagemDocumentoBulkAction::make(),
                    CreateAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
