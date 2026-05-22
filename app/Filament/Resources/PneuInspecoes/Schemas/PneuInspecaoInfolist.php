<?php

namespace App\Filament\Resources\PneuInspecoes\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PneuInspecaoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Dados da Inspeção')
                    ->columns(12)
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('pneu.numero_fogo')
                            ->label('Nº de Fogo')
                            ->columnSpan(2),
                        TextEntry::make('ciclo.numero')
                            ->label('Ciclo')
                            ->formatStateUsing(fn ($state) => filled($state) ? 'Ciclo ' . $state : 'N/A')
                            ->columnSpan(2),
                        TextEntry::make('tipo')
                            ->columnSpan(2),
                        TextEntry::make('resultado')
                            ->columnSpan(2),
                        TextEntry::make('data_inspecao')
                            ->label('Dt. Inspeção')
                            ->date('d/m/Y')
                            ->columnSpan(2),
                        TextEntry::make('km_referencia')
                            ->label('KM Referência')
                            ->numeric(0, ',', '.')
                            ->columnSpan(2),
                        TextEntry::make('veiculo.placa')
                            ->label('Veículo')
                            ->placeholder('N/A')
                            ->columnSpan(2),
                        TextEntry::make('posicaoVeiculo.posicao')
                            ->label('Posição')
                            ->placeholder('N/A')
                            ->columnSpan(2),
                        TextEntry::make('sulco_interno')
                            ->numeric(2, ',', '.')
                            ->columnSpan(2),
                        TextEntry::make('sulco_centro')
                            ->numeric(2, ',', '.')
                            ->columnSpan(2),
                        TextEntry::make('sulco_externo')
                            ->numeric(2, ',', '.')
                            ->columnSpan(2),
                        TextEntry::make('apto_recapagem')
                            ->label('Apto Recapagem')
                            ->formatStateUsing(fn ($state) => $state === null ? 'N/A' : ($state ? 'SIM' : 'NAO'))
                            ->columnSpan(2),
                        TextEntry::make('observacao')
                            ->label('Observação')
                            ->columnSpanFull(),
                        ImageEntry::make('anexos')
                            ->label('Anexos')
                            ->disk('local')
                            ->visibility('private')
                            ->imageHeight('12rem')
                            ->columnSpanFull()
                            ->openUrlInNewTab()
                            ->url(fn (ImageEntry $component, ?string $state): ?string => $component->getImageUrl($state)),
                    ]),
            ]);
    }
}
