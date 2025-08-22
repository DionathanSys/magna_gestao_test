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
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
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
                    ->width('1%')
                    ->url(fn (Models\OrdemServico $record): string => OrdemServicoResource::getUrl('custom', ['record' => $record->id]))
                    ->openUrlInNewTab(),
                TextColumn::make('sankhyaId.ordem_sankhya_id')
                    ->label('OS Sankhya')
                    ->width('1%'),
                TextColumn::make('veiculo.placa')
                    ->label('Veículo')
                    ->width('1%')
                    ->url(fn (Models\OrdemServico $record): string => OrdemServicoResource::getUrl('custom', ['record' => $record->id])),
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
                    ->width('1%')
                    ->date('d/m/Y'),
                TextColumn::make('data_fim')
                    ->label('Dt. Fim')
                    ->width('1%')
                    ->date('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('itens_count')->counts('itens')
                    ->label('Qtd. Serviços')
                    ->width('1%'),
                TextColumn::make('pendentes_count')->counts('pendentes')
                    ->label('Pendencias')
                    ->width('1%')
                    ->color(fn($state): string => $state == 0 ? 'gray' : 'info')
                    ->badge(fn($state): bool => $state > 0),
                TextColumn::make('status')
                    ->width('1%')
                    ->badge('success'),
                SelectColumn::make('status_sankhya')
                    ->label('Sankhya')
                    ->width('1%')
                    ->options(Enum\OrdemServico\StatusOrdemServicoEnum::toSelectArray()),
                TextColumn::make('parceiro.nome')
                    ->label('Fornecedor')
                    ->width('1%')
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i')
                    ->label('Criado Em')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime('d/m/Y H:i')
                    ->label('Editado Em')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creator.name')
                    ->label('Criado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
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
                EditAction::make(),
                Action::make('custom')
                    ->label('Custom')
                    ->icon('heroicon-o-cog')
                    ->iconButton()
                    ->url(fn (Models\OrdemServico $record): string => OrdemServicoResource::getUrl('custom', ['record' => $record->id])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
