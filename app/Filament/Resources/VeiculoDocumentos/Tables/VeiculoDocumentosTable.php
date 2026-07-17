<?php

namespace App\Filament\Resources\VeiculoDocumentos\Tables;

use App\Models\VeiculoDocumento;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VeiculoDocumentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('data_fim')
            ->columns([
                TextColumn::make('veiculo.placa')
                    ->label('Veículo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('veiculo.filial')
                    ->label('Unidade')
                    ->sortable(),
                TextColumn::make('nome')
                    ->label('Documento')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->formatStateUsing(fn (?string $state): string => VeiculoDocumento::tipoOptions()[$state] ?? ($state ?: '-'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('data_inicio')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('data_fim')
                    ->label('Fim')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('dias_restantes')
                    ->label('Dias Rest.')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '-' : number_format($state, 0, ',', '.'))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('data_fim', $direction)),
                TextColumn::make('dias_alerta')
                    ->label('Alerta')
                    ->numeric(0, ',', '.')
                    ->suffix(' dias')
                    ->sortable(),
                TextColumn::make('status_documento')
                    ->label('Status')
                    ->badge()
                    ->color(fn (VeiculoDocumento $record): string => $record->getStatusColor()),
                TextColumn::make('anexos')
                    ->label('Anexos')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? (string) count($state) : '0')
                    ->alignCenter(),
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
                    ->preload(),
                SelectFilter::make('nome')
                    ->label('Documento')
                    ->options(fn (): array => VeiculoDocumento::query()
                        ->whereNotNull('nome')
                        ->orderBy('nome')
                        ->pluck('nome', 'nome')
                        ->all())
                    ->searchable(),
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(VeiculoDocumento::tipoOptions()),
                Filter::make('vencidos')
                    ->label('Vencidos')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->whereDate('data_fim', '<', now()->toDateString())),
                Filter::make('em_alerta')
                    ->label('Em alerta')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('data_fim')
                        ->whereDate('data_fim', '>=', now()->toDateString())
                        ->whereRaw('DATEDIFF(data_fim, CURDATE()) <= dias_alerta')),
                Filter::make('vigencia')
                    ->form([
                        DatePicker::make('data_inicio')->label('Fim a partir de'),
                        DatePicker::make('data_fim')->label('Fim até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['data_inicio'] ?? null, fn (Builder $query, $date) => $query->whereDate('data_fim', '>=', $date))
                            ->when($data['data_fim'] ?? null, fn (Builder $query, $date) => $query->whereDate('data_fim', '<=', $date));
                    }),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
