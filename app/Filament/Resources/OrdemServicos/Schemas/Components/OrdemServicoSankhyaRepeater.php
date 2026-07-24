<?php

namespace App\Filament\Resources\OrdemServicos\Schemas\Components;

use Filament\Forms\Components\Repeater;
use Filament\Infolists\Components\TextEntry;

class OrdemServicoSankhyaRepeater
{
    public static function make(): Repeater
    {
        return Repeater::make('sankhyaId')
            ->label('OS Sankhya')
            ->relationship()
            ->columns(12)
            ->columnSpanFull()
            // ->addActionLabel('Adicionar OS Sankhya')
            ->addable(false)
            ->schema([
                TextEntry::make('id')
                    ->label('ID')
                    ->columnSpan([
                        'default' => 6,
                        'md' => 2,
                        'lg' => 4,
                    ]),
                TextEntry::make('ordem_sankhya_id')
                    ->label('OS Sankhya')
                    ->columnSpan([
                        'default' => 6,
                        'md' => 4,
                        'lg' => 8,
                    ]),
            ]);
    }
}
