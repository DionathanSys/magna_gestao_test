<?php

namespace App\Filament\Resources\Veiculos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class VeiculosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('placa')
                    ->searchable(),
                TextColumn::make('filial')
                    ->label('Filial'),
                TextColumn::make('kmAtual.quilometragem')
                    ->label('KM Atual')
                    ->numeric(0, ',', '.'),
                TextColumn::make('km_medio')
                    ->label('KM Médio/Dia')
                    ->numeric(2, ',', '.'),
                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
                TextColumn::make('marca')
                    ->label('Marca')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('modelo')
                    ->label('Modelo')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('chassis')
                    ->label('Chassi')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('informacoes_complementares.afericao_tacografo')
                    ->label('Dt. Próx. Aferição Tacógrafo')
                    ->date('d/m/Y')
                    ->badge()
                    ->color(fn($state): string => match (true) {
                        !$state => 'gray',
                        $state <= now()->addDays(30) => 'danger',
                        $state <= now()->addDays(60) => 'warning',
                        default => 'success'
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('informacoes_complementares.teste_fumaca')
                    ->label('Dt. Teste de Fumaça')
                    ->date('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color(fn($state): string => match (true) {
                        !$state => 'success',
                        $state <= now()->subDays(180) => 'danger',
                        $state <= now()->subDays(165) => 'warning',
                        $state <= now()->subDays(150) => 'info',
                        default => 'primary',
                    })
                    ->formatStateUsing(
                        fn($state) => $state
                            ? \Carbon\Carbon::parse($state)->format('d/m/Y') . ' (' . \Carbon\Carbon::parse($state)->diffInDays(now()) . ' dias atrás)'
                            : 'Sem data'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('informacoes_complementares.codigo_imobilizado')
                    ->label('Código Imobilizado')
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
                TextColumn::make('deleted_at')
                    ->label('Excluído Em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('filial')
                    ->options([
                        'CATANDUVAS' => 'Catanduvas',
                        'CHAPECO'    => 'Chapecó',
                        'CONCORDIA'  => 'Concórdia',
                    ])
                    ->default(fn() => Auth::user()->name == 'Carol' ? 'CATANDUVAS' : 'CHAPECO')
                    ->selectablePlaceholder(false),
                Filter::make('is_active')
                    ->label('Ativo')
                    ->toggle()
                    ->default(true)
                    ->query(fn($query) => $query->where('is_active', true)),
                TrashedFilter::make(),
            ])
            ->paginated(false)
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
