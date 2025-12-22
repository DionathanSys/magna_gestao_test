<?php

namespace App\Filament\Tables;

use App\Models\DocumentoFrete;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class SelectDocumentoFrete
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => DocumentoFrete::query())
            ->modifyQueryUsing(function (Builder $query) use ($table): Builder {

                $arguments = $table->getArguments();

                if (isset($arguments['veiculo_id'])) {
                    $query->where('veiculo_id', $arguments['veiculo_id']);
                }

                // Exclui documentos que já estão vinculados a uma ViagemBugio
                $query->whereDoesntHave('viagemBugio');

                return $query;
            })
            ->columns([
                TextColumn::make('veiculo.placa')
                    ->label('Placa')
                    ->searchable(),
                TextColumn::make('parceiro_origem')
                    ->label('Origem')
                    ->searchable(),
                TextColumn::make('parceiro_destino')
                    ->label('Destino')
                    ->searchable(),
                TextColumn::make('numero_documento')
                    ->label('Nro. Documento')
                    ->searchable(),
                TextColumn::make('documento_transporte')
                    ->label('Doc. Transporte')
                    ->searchable(),
                TextColumn::make('tipo_documento')
                    ->label('Tipo Doc.')
                    ->badge()
                    ->searchable(),
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
                TextColumn::make('valor_liquido')   
                    ->label('Frete Líquido')
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('municipio')
                    ->label('Município'),
                TextColumn::make('estado')
                    ->label('Estado'),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('viagem.id')
                    ->searchable(),
                TextColumn::make('resultadoPeriodo.id')
                    ->searchable(),
            ])
            ->filters([
                DateRangeFilter::make('data_emissao')
                    ->label('Dt. Emissão')
                    ->autoApply()
                    ->firstDayOfWeek(0)
                    ->alwaysShowCalendar(),
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
