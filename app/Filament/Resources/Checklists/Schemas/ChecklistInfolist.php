<?php

namespace App\Filament\Resources\Checklists\Schemas;

// use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ChecklistInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                TextEntry::make('veiculo.placa')
                    ->label('Veículo')
                    ->columnSpan(2),
                TextEntry::make('data_referencia')
                    ->label('Data Realização')
                    ->date('d/m/Y')
                    ->columnSpan(2),
                TextEntry::make('periodo')
                    ->label('Período')
                    ->date('F/Y')
                    ->columnSpan(2),
                TextEntry::make('quilometragem')
                    ->label('Quilometragem')
                    ->columnSpan(2)
                    ->numeric(0, ',', '.'),
                TextEntry::make('status')
                    ->label('Status')
                    ->columnSpan(2),
                TextEntry::make('creator.name')
                    ->label('Criado por')
                    ->columnSpan(2),
                RepeatableEntry::make('itens_verificados')
                    ->label('Itens Verificados')
                    ->columns(7)
                    ->grid(3)
                    ->schema([
                        TextEntry::make('item')
                            ->columnSpan(3),
                        TextEntry::make('status')
                            ->columnSpan(2)
                            ->formatStateUsing(fn ($state) => $state ? 'OK' : 'NOK')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                true => 'success',
                                false => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('corrigido')
                            ->columnSpan(2)
                            ->formatStateUsing(fn ($state) => $state ? 'Sim' : ''),
                        TextEntry::make('observacoes')
                            ->columnSpan(6)
                            ->placeholder('Sem observações'),
                    ])
                    ->columnSpanFull(),
                RepeatableEntry::make('itens_corrigidos')
                    ->label('Itens Corrigidos')
                    ->columns(7)
                    ->grid(3)
                    ->schema([
                        TextEntry::make('item')
                            ->columnSpan(3),
                        TextEntry::make('status')
                            ->columnSpan(2)
                            ->formatStateUsing(fn ($state) => $state ? 'OK' : 'NOK')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                true => 'success',
                                false => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('corrigido')
                            ->columnSpan(2)
                            ->formatStateUsing(fn ($state) => $state ? 'Sim' : ''),
                        TextEntry::make('observacoes')
                            ->columnSpan(6)
                            ->placeholder('Sem observações'),
                    ])
                    ->placeholder('Nenhum item foi corrigido neste checklist')
                    ->columnSpanFull(),
                RepeatableEntry::make('pendencias')
                    ->label('Pendências')
                    ->placeholder('Nenhuma pendência encontrada! ✅')
                    ->columns(7)
                    ->grid(3)
                    ->schema([
                        TextEntry::make('item')
                            ->columnSpan(3),
                        TextEntry::make('status')
                            ->columnSpan(2)
                            ->formatStateUsing(fn ($state) => $state ? 'OK' : 'NOK')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                true => 'success',
                                false => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('corrigido')
                            ->columnSpan(2)
                            ->formatStateUsing(fn ($state) => $state ? 'Sim' : ''),
                        TextEntry::make('observacoes')
                            ->columnSpan(6)
                            ->placeholder('Sem observações'),
                    ])
                    ->columnSpanFull(),

            ]);
    }
}
