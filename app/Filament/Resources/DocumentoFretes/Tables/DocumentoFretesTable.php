<?php

namespace App\Filament\Resources\DocumentoFretes\Tables;

use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;
use App\{Models};
use App\Enum\Frete\TipoDocumentoEnum;
use App\Filament\Actions\DefinirResultadoPeriodoBulkAction;
use App\Filament\Components\RegistrosSemVinculoResultadoFilter;
use App\Filament\Actions\DissociateResultadoPeriodoBulkAction;
use App\Filament\Resources\DocumentoFretes\Actions;
use App\Filament\Resources\DocumentoFretes\Actions\CriarViagemBulkAction;
use App\Filament\Resources\DocumentoFretes\Actions\VincularResultadoPeriodoBulkAction;
use App\Filament\Resources\Viagems\ViagemResource;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class DocumentoFretesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['veiculo:id,placa', 'resultadoPeriodo:id,data_inicio']))
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
                    ->searchable(isIndividual: true)
                    ->toggleable(isToggledHiddenByDefault: false),
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
                    ->width('1%')
                    ->disabledClick()
                    ->searchable(isIndividual: true)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('viagem_id')
                    ->label("viagem ID")
                    ->url(fn(Models\DocumentoFrete $record): string => ViagemResource::getUrl('view', ['record' => $record->viagem_id ?? 0]))
                    ->openUrlInNewTab(),
                TextInputColumn::make('resultado_periodo_id')
                    ->label("Resultado Período ID")
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('resultadoPeriodo.data_inicio')
                    ->label('Resultado Período')
                    ->disabledClick()
                    ->date('d/m/Y')
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
            ->searchDebounce(850)
            ->persistSortInSession()
            ->defaultSortOptionLabel('created_at')
            ->persistFiltersInSession()
            ->persistColumnSearchesInSession()
            ->paginated([25, 50, 100, 250, 500])
            ->extremePaginationLinks()
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
                DateRangeFilter::make('resultadoPeriodo.data_inicio')
                    ->label('Resultado Período')
                    ->autoApply()
                    ->firstDayOfWeek(0)
                    ->alwaysShowCalendar()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'] ?? null,
                                fn(Builder $query, $date): Builder =>
                                $query->whereHas(
                                    'resultadoPeriodo',
                                    fn(Builder $q) =>
                                    $q->whereDate('data_inicio', '>=', $date)
                                )
                            )
                            ->when(
                                $data['end_date'] ?? null,
                                fn(Builder $query, $date): Builder =>
                                $query->whereHas(
                                    'resultadoPeriodo',
                                    fn(Builder $q) =>
                                    $q->whereDate('data_inicio', '<=', $date)
                                )
                            );
                    }),
                SelectFilter::make('resultado_periodo_id')
                    ->label('Com Resultado Período')
                    ->relationship('resultadoPeriodo', 'data_inicio')
                    ->getOptionLabelFromRecordUsing(fn(Models\ResultadoPeriodo $record): string =>
                        Carbon::parse($record->data_inicio)->format('d/m/Y') . ' (ID: ' . $record->id . ' - Veículo: ' . $record->veiculo->placa . ')'
                    )
                    ->searchable(),
                Filter::make('sem_vinculo_viagem')
                    ->label('Sem Viagem')
                    ->toggle()
                    ->query(fn(Builder $query): Builder => $query->whereNull('viagem_id')),
                RegistrosSemVinculoResultadoFilter::make(),
            ])
            ->groups([
                Group::make('veiculo.placa')
                    ->label('Placa')
                    ->collapsible(),
                Group::make('data_emissao')
                    ->label('Dt. Emissão')
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(fn(Models\DocumentoFrete $record): string => Carbon::parse($record->data_emissao)->format('d/m/Y'))
                    ->collapsible(),
                Group::make('parceiro_origem')
                    ->label('Parceiro Origem')
                    ->collapsible(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton(),
                EditAction::make()
                    ->iconButton(),
            ])
            ->headerActions([])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn(): bool => Auth::user()->is_admin),
                    DissociateResultadoPeriodoBulkAction::make(),
                    DefinirResultadoPeriodoBulkAction::make(),
                    VincularResultadoPeriodoBulkAction::make(),
                    CriarViagemBulkAction::make(),
                    FilamentExportBulkAction::make('export')
                ]),
                ActionGroup::make([
                    CreateAction::make()
                        ->preserveFormDataWhenCreatingAnother(['veiculo_id', 'parceiro_origem', 'documento_transporte', 'tipo_documento', 'data_emissao', 'valor_total']),
                    Actions\VincularViagemDocumentoBulkAction::make(),
                    Actions\ImportarDocumentoFretePdfAction::make(),
                    Actions\ImportarDocumentoFreteAction::make(),
                    Actions\ImportarDocumentoFreteNutrepampaAction::make(),
                ])->button(),
            ])
            ->striped();
    }
}
