<?php

namespace App\Filament\Resources\DocumentoFretes\Tables;

use App\{Models};
use App\Enum\Frete\TipoDocumentoEnum;
use App\Filament\Resources\DocumentoFretes\Actions;
use App\Filament\Resources\Viagems\ViagemResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
use Illuminate\Support\Facades\Auth;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class DocumentoFretesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with(['veiculo:id,placa']);
            })
            ->columns([
                TextColumn::make('veiculo.placa')
                    ->label('Placa')
                    ->width('1%')
                    ->searchable(isIndividual: true),
                TextColumn::make('numero_documento')
                    ->label('Nro. Documento')
                    ->disabledClick()
                    ->width('1%')
                    ->searchable(isIndividual: true),
                TextColumn::make('documento_transporte')
                    ->label('Nro. Doc. Transp.')
                    ->disabledClick()
                    ->width('1%')
                    ->searchable(isIndividual: true),
                TextColumn::make('tipo_documento')
                    ->label('Tipo Documento')
                    ->width('1%')
                    ->searchable(isIndividual: true),
                TextColumn::make('viagem_id')
                    ->label("viagem ID")
                    ->width('1%')
                    ->url(fn(Models\DocumentoFrete $record): string => ViagemResource::getUrl('view', ['record' => $record->viagem_id ?? 0]))
                    ->openUrlInNewTab(),
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
                    ->summarize(Sum::make()
                        ->money('BRL', 100)),
                TextColumn::make('valor_icms')
                    ->label('Vlr. ICMS')
                    ->width('1%')
                    ->money('BRL')
                    ->sortable()
                    ->summarize(Sum::make()
                        ->money('BRL', 100)),
                TextColumn::make('valor_liquido')
                    ->label('Frete Líquido')
                    ->width('1%')
                    ->money('BRL')
                    ->sortable()
                    ->summarize(Sum::make()
                        ->money('BRL', 100)),
                TextColumn::make('parceiro_origem')
                    ->label('Parceiro Origem')
                    ->disabledClick()
                    ->width('1%')
                    ->searchable(isIndividual: true)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('parceiro_destino')
                    ->label('Parceiro Destino')
                    ->disabledClick()
                    ->searchable(isIndividual: true)
                    ->toggleable(isToggledHiddenByDefault: false),
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
            ->searchDebounce(850)
            ->persistSortInSession()
            ->defaultSortOptionLabel('created_at')
            ->persistFiltersInSession()
            ->filters([
                SelectFilter::make('veiculo_id')
                    ->label('Veículo')
                    ->relationship('veiculo', 'placa')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('tipo_documento')
                    ->multiple()
                    ->options(TipoDocumentoEnum::toSelectArray()),
                DateRangeFilter::make('data_emissao')
                    ->label('Dt. Emissão')
                    ->autoApply()
                    ->firstDayOfWeek(0)
                    ->alwaysShowCalendar(),
                Filter::make('sem_vinculo_viagem')
                    ->label('Sem Viagem')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->whereNull('viagem_id')),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton(),
                EditAction::make()
                    ->iconButton(),
            ])
            ->headerActions([
                
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()    
                        ->visible(fn(): bool => Auth::user()->is_admin),
                ]),
                ActionGroup::make([
                    Actions\VincularViagemDocumentoBulkAction::make(),
                    CreateAction::make()
                        ->preserveFormDataWhenCreatingAnother(['veiculo_id', 'parceiro_origem', 'documento_transporte', 'tipo_documento', 'data_emissao', 'valor_total']),
                    Actions\ImportarDocumentoFreteAction::make(),
                    Action::make('importar_espelho_frete')
                        ->label('Importar Espelho de Frete')
                        ->icon('heroicon-o-upload')
                        ->url(route('import.pdf'))
                        ->openUrlInNewTab()
                        ->color('success'),
                ])->button(),
            ]);
    }
}
