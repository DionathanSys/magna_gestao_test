<?php

namespace App\Filament\Resources\OrdemServicos\Schemas\Components;

use App\{Models, Enum, Services};
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OrdemServicoSankhyaRepeater
{
    public static function make(): Repeater
    {
        return Repeater::make('sankhyaId')
            ->label('OS Sankhya')
            ->relationship()
            ->columns(12)
            ->columnSpanFull()
            ->addActionLabel('Adicionar OS Sankhya')
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
