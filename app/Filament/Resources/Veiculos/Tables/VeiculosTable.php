<?php

namespace App\Filament\Resources\Veiculos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
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
                ToggleColumn::make('is_active')
                    ->label('Ativo'),
                TextColumn::make('marca')
                    ->label('Marca')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('modelo')
                    ->label('Modelo')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Select::make('tipoVeiculo.descricao')
                    ->label('Tipo de Veículo')
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
                        !$state => 'success',
                        $state <= now()->addDays(30) => 'danger',
                        $state <= now()->addDays(60) => 'warning',
                        default => 'primary'
                    })
                    ->formatStateUsing(
                        function($state) {

                            if (!$state) {
                                return 'Sem data';
                            }

                            $days = \Carbon\Carbon::parse(now())->diffInDays($state, false);

                            if ($days === 0) {
                                return \Carbon\Carbon::parse($state)->format('d/m/Y') . ' Hoje!';
                            } elseif ($days === 1) {
                                return \Carbon\Carbon::parse($state)->format('d/m/Y') . ' Amanhã!';
                            } elseif ($days < 0) {
                                return \Carbon\Carbon::parse($state)->format('d/m/Y') . ' Vencido!';
                            } elseif ($days < 61) {
                                return \Carbon\Carbon::parse($state)->format('d/m/Y') . ' Faltam ' . number_format($days, 0, ',', '.') . ' dias';
                            } else {
                                return \Carbon\Carbon::parse($state)->format('d/m/Y');

                        }
                        }
                    )
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
                            ? \Carbon\Carbon::parse($state)->format('d/m/Y') . ' (' . number_format(\Carbon\Carbon::parse($state)->diffInDays(now()), 0) . ' dias atrás)'
                            : 'Sem data'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('informacoes_complementares.data_ultimo_checklist')
                    ->label('Dt. Último Checklist')
                    ->date('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(
                        fn($state) => $state
                            ? \Carbon\Carbon::parse($state)->format('d/m/Y') . ' (' . number_format(\Carbon\Carbon::parse($state)->diffInDays(now()), 0) . ' dias atrás)'
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
                Filter::make('checklist')
                    ->label('Sem Checklist no Mês')
                    ->query(function ($query) {
                        $query->where(function ($query) {
                            $query->whereNull('informacoes_complementares->data_ultimo_checklist')
                                ->orWhere('informacoes_complementares->data_ultimo_checklist', '<', now()->startOfMonth()->format('Y-m-d'));
                        });
                    })
                    ->toggle(),
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
