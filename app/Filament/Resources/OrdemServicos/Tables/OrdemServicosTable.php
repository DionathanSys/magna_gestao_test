<?php

namespace App\Filament\Resources\OrdemServicos\Tables;

use App\Models;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Enum;
use App\Filament\Resources\OrdemServicos\Actions;
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Models\OrdemServico;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\Width;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\{SelectFilter, Filter};
use Illuminate\Database\Eloquent\Builder;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class OrdemServicosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['sankhyaId', 'veiculo', 'parceiro', 'creator']);
                return $query->withCount(['itens', 'pendentes']);
            })
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width('1%')
                    ->searchable(),
                TextColumn::make('sankhyaId.ordem_sankhya_id')
                    ->label('OS Sankhya')
                    ->width('1%')
                    ->searchable(),
                TextColumn::make('veiculo.placa')
                    ->label('Veículo')
                    ->sortable()
                    ->width('1%'),
                TextColumn::make('quilometragem')
                    ->label('Quilometragem')
                    ->width('1%')
                    ->numeric(0, ',', '.')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('tipo_manutencao')
                    ->label('Tipo Manutenção')
                    ->width('1%'),
                TextColumn::make('data_inicio')
                    ->label('Dt. Inicio')
                    ->sortable()
                    ->width('1%')
                    ->date('d/m/Y'),
                TextColumn::make('data_fim')
                    ->label('Dt. Fim')
                    ->sortable()
                    ->width('1%')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('itens_count')->counts('itens')
                    ->label('Qtd. Serviços')
                    ->width('1%'),
                TextColumn::make('pendentes_count')->counts('pendentes')
                    ->label('Pendências')
                    ->width('1%')
                    ->color(fn($state): string => $state == 0 ? 'gray' : 'info')
                    ->badge(fn($state): bool => $state > 0),
                TextColumn::make('status')
                    ->width('1%')
                    ->badge('success'),
                SelectColumn::make('status_sankhya')
                    ->label('Sankhya')
                    ->width('1%')
                    ->native(true)
                    ->options(Enum\OrdemServico\StatusOrdemServicoEnum::toSelectArray()),
                TextColumn::make('parceiro.nome')
                    ->label('Fornecedor')
                    ->width('1%')
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->width('1%')
                    ->sortable()
                    ->label('Criado Em')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('creator.name')
                    ->label('Criado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i')
                    ->label('Editado Em')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->persistFiltersInSession()
            ->searchable(['sankhyaId.ordem_sankhya_id'])
            ->filters([
                SelectFilter::make('veiculo_id')
                    ->label('Veículo')
                    ->relationship('veiculo', 'placa')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('parceiro_id')
                    ->label('Fornecedor')
                    ->relationship('parceiro', 'nome')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('tipo_manutencao')
                    ->label('Tipo Manutenção')
                    ->options(Enum\OrdemServico\TipoManutencaoEnum::toSelectArray())
                    ->multiple(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Enum\OrdemServico\StatusOrdemServicoEnum::toSelectArray())
                    ->multiple(),
                DateRangeFilter::make('data_inicio')
                    ->label('Dt. Abertura')
                    ->alwaysShowCalendar(),
                DateRangeFilter::make('data_fim')
                    ->label('Dt. Fechamento')
                    ->alwaysShowCalendar(),
                DateRangeFilter::make('created_at')
                    ->label('Dt. Registro'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Actions\EncerrarOrdemServicoAction::make(),
                    EditAction::make()
                        ->url(fn(Models\OrdemServico $record): string => OrdemServicoResource::getUrl('custom', ['record' => $record->id])),
                    Actions\PdfOrdemServicoAction::make(),
                    Actions\VincularOrdemSankhyaAction::make(),
                ])
                    ->icon('heroicon-o-bars-3-center-left')
                    ->dropdownPlacement('top-start'),
                ViewAction::make()
                    ->label('Visualizar')
                    ->color('primary')
                    ->modalWidth(Width::FiveExtraLarge)
                    ->iconButton(),
                Actions\VincularServicoOrdemServicoAction::make(fn($record) => $record->id)
                    ->iconButton()
                    ->mutateDataUsing(function (OrdemServico $record, array $data): array {
                        $data['ordem_servico_id'] = $record->id;
                        return $data;
                    }),
            ], RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
            ])
            ->striped()
            ->poll('5s');
    }
}
