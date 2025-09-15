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
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\Width;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\{SelectFilter, Filter};
use Illuminate\Database\Eloquent\Builder;

class OrdemServicosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->width('1%'),
                TextColumn::make('sankhyaId.ordem_sankhya_id')
                    ->label('OS Sankhya')
                    ->width('1%'),
                TextColumn::make('veiculo.placa')
                    ->label('Veículo')
                    ->sortable()
                    ->width('1%'),
                TextColumn::make('quilometragem')
                    ->label('Quilometragem')
                    ->width('1%')
                    ->numeric(0, ',', '.')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->native(false)
                    ->options(Enum\OrdemServico\StatusOrdemServicoEnum::toSelectArray()),
                TextColumn::make('parceiro.nome')
                    ->label('Fornecedor')
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->label('Criado Em')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i')
                    ->label('Editado Em')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creator.name')
                    ->label('Criado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->persistFiltersInSession()
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
                Filter::make('data_inicio')
                    ->schema([
                        DatePicker::make('data_inicio')
                            ->label('Dt. Abertura de'),
                        DatePicker::make('data_fim')
                            ->label('Dt. Abertura até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['data_inicio'],
                                fn(Builder $query, $date): Builder => $query->whereDate('data_inicio', '>=', $date),
                            )
                            ->when(
                                $data['data_fim'],
                                fn(Builder $query, $date): Builder => $query->whereDate('data_inicio', '<=', $date),
                            );
                    }),
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

            ], RecordActionsPosition::BeforeColumns)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
