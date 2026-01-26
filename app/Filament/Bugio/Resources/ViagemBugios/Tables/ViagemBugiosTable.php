<?php

namespace App\Filament\Bugio\Resources\ViagemBugios\Tables;

use App\Enum\Frete\TipoDocumentoEnum;
use App\Filament\Bugio\Resources\ViagemBugios\Actions\VincularDocumentoFreteAction;
use App\Filament\Bugio\Resources\ViagemBugios\Actions\VincularDocumentoFreteBulkAction;
use App\Filament\Bugio\Resources\ViagemBugios\Actions\VincularViagemAction;
use App\Jobs\SolicitarCteBugio;
use App\Models\ViagemBugio;
use App\Services\ViagemBugio\ViagemBugioService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class ViagemBugiosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll(null)
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->width('1%')
                    ->sortable()
                    ->searchable(isIndividual: true),
                TextColumn::make('veiculo.placa')
                    ->label('Placa')
                    ->width('1%')
                    ->sortable(),
                TextColumn::make('destinos')
                    ->label('Integrados')
                    ->width('1%')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return '-';
                        }

                        // Verificar se é um array associativo único ou array de arrays
                        if (isset($state['integrado_nome'])) {
                            // É um único destino
                            return $state['integrado_nome'];
                        }

                        // É um array de destinos
                        return collect($state)
                            ->pluck('integrado_nome')
                            ->filter()
                            ->join(', ');
                    }),
                TextColumn::make('nro_documento')
                    ->label('Nº Doc. Frete')
                    ->width('1%')
                    ->sortable()
                    ->searchable(isIndividual: true),
                TextColumn::make('nro_notas')
                    ->label('Nro Notas')
                    ->width('1%')
                    ->disabledClick()
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return '-';
                        }

                        // Verificar se é um array associativo único ou array de arrays
                        if (is_string($state)) {
                            return $state;
                        }

                        // É um array de notas
                        return collect($state)
                            ->filter()
                            ->join(', ');
                    })
                    ->searchable(isIndividual: true),
                TextColumn::make('numero_sequencial')
                    ->label('Nº Sequencial')
                    ->width('1%')
                    ->disabledClick()
                    ->formatStateUsing(function ($state) {
                        return $state ? str_pad($state, 6, '0', STR_PAD_LEFT) : '-';
                    })
                    ->sortable()
                    ->searchable(isIndividual: true),
                TextColumn::make('tipo_documento_extraido')
                    ->label('Tipo Doc.')
                    ->width('1%')
                    ->state(function ($record) {
                        if ($record->info_adicionais) {
                            $info = is_string($record->info_adicionais) 
                                ? json_decode($record->info_adicionais, true) 
                                : $record->info_adicionais;
                            
                            if (is_array($info) && isset($info['tipo_documento'])) {
                                $tipoDocumento = $info['tipo_documento'];
                                
                                // Se for CTe Complemento, retorna o valor de cte_referencia
                                if ($tipoDocumento === 'CTe Complemento' && isset($info['cte_referencia'])) {
                                    return 'Complemto ao CTe ' . $info['cte_referencia'];
                                }
                                
                                return $tipoDocumento;
                            }
                        }
                        return '-';
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('data_competencia')
                    ->label('Data Viagem')
                    ->width('1%')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(isIndividual: true),
                TextColumn::make('km_pago')
                    ->label('Km Pago')
                    ->width('1%')
                    ->numeric(0, ',', '.')
                    ->sortable()
                    ->summarize(Sum::make()),
                TextColumn::make('frete')
                    ->label('Frete')
                    ->width('1%')
                    ->money('BRL')
                    ->sortable()
                    ->summarize(Sum::make()
                        ->money('BRL')),
                TextColumn::make('condutor')
                    ->label('Motorista')
                    ->width('1%')
                    ->sortable()
                    ->searchable(isIndividual: true)
                    ->toggleable(isToggledHiddenByDefault: false),
                SelectColumn::make('status')
                    ->label('Status')
                    ->width('1%')
                    ->options([
                        'pendente' => 'Pendente',
                        'em_andamento' => 'CTe solicitado',
                        'concluido' => 'CTe emitido',
                        'cancelada' => 'Cancelada',
                    ])
                    ->sortable(),
                TextColumn::make('viagem.numero_viagem')
                    ->label('Viagem Vinculada')
                    ->width('1%')
                    ->sortable()
                    ->searchable(isIndividual: true)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('documento.numero_documento')
                    ->label('Doc. Frete Vinculado')
                    ->width('1%')
                    ->sortable()
                    ->searchable(isIndividual: true)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('creator.name')
                    ->label('Criado Por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Criado Em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
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
                    ->searchable()
                    ->preload()
                    ->multiple(),
                DateRangeFilter::make('data_competencia')
                    ->label('Data da Viagem')
                    ->autoApply()
                    ->firstDayOfWeek(0)
                    ->alwaysShowCalendar(),
                DateRangeFilter::make('created_at')
                    ->label('Data de Criação')
                    ->autoApply()
                    ->firstDayOfWeek(0)
                    ->alwaysShowCalendar(),
                SelectFilter::make('tipo_documento')
                    ->label('Tipo Documento')
                    ->options(TipoDocumentoEnum::toSelectArray())
                    ->multiple(),
                Filter::make('viagem')
                    ->schema([
                        Select::make('status_viagem')
                            ->options([
                                'com' => 'Com viagem',
                                'sem' => 'Sem viagem',
                            ])
                            ->placeholder('Todos'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['status_viagem'] === 'com',
                                fn(Builder $query) => $query->whereHas('viagem'),
                            )
                            ->when(
                                $data['status_viagem'] === 'sem',
                                fn(Builder $query) => $query->whereDoesntHave('viagem'),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $status = $data['status_viagem'] ?? null;

                        if (! $status) {
                            return null;
                        }

                        return match ($status) {
                            'com' => 'Com viagem',
                            'sem' => 'Sem viagem',
                            default => null,
                        };
                    })

            ])
            ->reorderableColumns()
            ->persistSortInSession()
            ->searchOnBlur()
            ->deferFilters()
            ->persistFiltersInSession()
            ->deselectAllRecordsWhenFiltered(false)
            ->defaultSort('created_at', 'desc')
            ->defaultGroup('data_competencia')
            ->groupingDirectionSettingHidden()
            ->paginated([15, 25, 50, 100])
            ->defaultPaginationPageOption(15)
            ->groups(
                [
                    Group::make('data_competencia')
                        ->label('Data Competência')
                        ->titlePrefixedWithLabel(false)
                        ->getTitleFromRecordUsing(fn(ViagemBugio $record): string => Carbon::parse($record->data_competencia)->format('d/m/Y'))
                        ->collapsible()
                        ->orderQueryUsing(fn(Builder $query, string $direction) => $query->orderBy('data_competencia', 'desc')),
                    Group::make('veiculo.placa')
                        ->label('Veículo')
                        ->titlePrefixedWithLabel(false)
                        ->collapsible(),
                ]
            )
            ->defaultSort('data_competencia', 'desc')
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn() => Auth::user()->is_admin),
                    VincularViagemAction::make()
                        ->icon(Heroicon::Link),
                ]),
                Action::make('solicitar-email')
                    ->label('Solicitar CTe')
                    ->tooltip('Solicitar CTe')
                    ->icon(Heroicon::PaperAirplane)
                    ->color('info')
                    ->iconButton()
                    ->action(function (ViagemBugio $record) {
                        $bugioService = new ViagemBugioService();
                        $bugioService->solicitarCte($record);
                    })
                    ->requiresConfirmation(),
                Action::make('inserir_nro_documento')
                    ->schema([
                        TextInput::make('nro_documento')
                            ->label('Nro. CTe emitido')
                            ->minValue(0)
                            ->required()
                    ])
                    ->color('success')
                    ->iconButton()
                    ->icon(Heroicon::ClipboardDocumentCheck)
                    ->disabled(fn(ViagemBugio $record) => $record->nro_documento != null)
                    ->action(function (ViagemBugio $record, $data) {

                        Validator::make($data, [
                            'nro_documento' => 'required|numeric|min:1'
                        ])->validate();

                        $record->update([
                            'nro_documento' => $data['nro_documento'],
                        ]);

                        Log::info("Nro. CTe {$data['nro_documento']} inserido para ViagemBugio ID {$record->id} pelo usuário " . Auth::user()->id, [
                            'record' => $record->toArray(),
                        ]);

                        $bugioService = new ViagemBugioService();
                        $bugioService->createViagemFromBugio($record);
                    })


            ], position: RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => Auth::user()->is_admin),
                ]),
            ]);
    }
}
