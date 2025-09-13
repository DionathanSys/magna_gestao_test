<?php

namespace App\Filament\Resources\Pneus\Schemas;

use App\Models;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class PneuInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Informações Gerais')
                    ->columns(12)
                    ->columnSpan(12)
                    ->components([
                        TextEntry::make('id')
                            ->label('ID')
                            ->columnSpan(1),
                        TextEntry::make('numero_fogo')
                            ->label('Nº de Fogo')
                            ->weight(FontWeight::Bold)
                            ->badge()
                            ->columnSpan(2),
                        TextEntry::make('marca')
                            ->label('Marca')
                            ->weight(FontWeight::Bold)
                            ->columnSpan(2),
                        TextEntry::make('modelo')
                            ->label('Modelo Carcaça')
                            ->weight(FontWeight::Bold)
                            ->columnSpan(2),
                        TextEntry::make('medida')
                            ->label('Medida Carcaça')
                            ->weight(FontWeight::Bold)
                            ->columnSpan(2),
                    ]),
                Section::make('Status')
                    ->columns(12)
                    ->columnSpan(12)
                    ->components([
                        TextEntry::make('ciclo_vida')
                            ->label('Ciclo de Vida Atual')
                            ->columnSpan(2),
                        TextEntry::make('desenhoPneu.descricao')
                            ->label('Desenho Borracha Atual')
                            ->columnSpan(2),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->columnSpan(2),
                        TextEntry::make('local')
                            ->label('Local')
                            ->columnSpan(2),
                        TextEntry::make('veiculo.placa')
                            ->label('Veículo')
                            ->columnSpan(2),
                        TextEntry::make('km_percorrido_ciclo')
                            ->label('KM Percorrido no Ciclo')
                            ->columnStart(1)
                            ->columnSpan(2),
                        TextEntry::make('km_percorrido')
                            ->label('KM Percorrido Total')
                            ->columnSpan(2),
                    ]),
                Section::make('Informações de Ult. Movimentação')
                    ->columns(12)
                    ->columnSpan(12)
                    ->components([
                        TextEntry::make('posicao_eixo')
                            ->label('Posição/Eixo')
                            ->state(function (Models\Pneu $record) {
                                $ultMov = $record->historicoMovimentacao()->latest();
                                return $ultMov->exists() ? $ultMov->value('eixo') . '° Eixo ' . $ultMov->value('posicao'): 'Não informado';
                            })

                            ->placeholder('Não informado')
                            ->columnSpan(2),
                        TextEntry::make('data_inicial')
                            ->label('Dt. Ult. Aplicação')
                            ->state(
                                fn(Models\Pneu $record) => $record->historicoMovimentacao()
                                    ->latest()
                                    ->value('data_inicial')
                            )
                            ->date('d/m/Y')
                            ->placeholder('Não informado')
                            ->columnSpan(2),
                        TextEntry::make('data_final')
                            ->label('Dt. Ult. Remoção')
                            ->state(
                                fn(Models\Pneu $record) => $record->historicoMovimentacao()
                                    ->latest()
                                    ->value('data_final')
                            )
                            ->date('d/m/Y')
                            ->placeholder('Não informado')
                            ->columnSpan(2),
                    ]),
            ]);
    }
}
