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
            ->components([
                TextEntry::make('veiculo_id')
                    ->numeric(),
                TextEntry::make('data_referencia')
                    ->date(),
                TextEntry::make('periodo')
                    ->date(),
                TextEntry::make('quilometragem')
                    ->numeric(),
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
                            ->formatStateUsing(fn ($state) => $state ? 'Sim' : '')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                true => 'success',
                                false => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('observacoes')
                            ->columnSpan(6)
                            ->placeholder('Sem observações'),
                    ])
                    ->columnSpanFull(),
                TextEntry::make('status'),
                TextEntry::make('created_by')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
