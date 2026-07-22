<?php

namespace App\Filament\Resources\Pneus\Schemas;

use App\Filament\Resources\Pneus\Schemas\Components\CiclosVidaSection;
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
                        TextEntry::make('posicao_eixo')
                            ->label('Posição/Eixo')
                            ->state(function (Models\Pneu $record) {
                                $ultMov = $record->posicaoVeiculo();

                                return $ultMov->exists() ? $ultMov->value('eixo').'° Eixo '.$ultMov->value('posicao') : 'Não informado';
                            })
                            ->placeholder('Não informado')
                            ->columnSpan(2),
                        TextEntry::make('ciclo_vida')
                            ->label('Ciclo de Vida Atual')
                            ->weight(FontWeight::Bold)
                            ->columnSpan(2),
                        TextEntry::make('numero_fogo')
                            ->label('Nº de Fogo')
                            ->weight(FontWeight::Bold)
                            ->columnSpan(2),
                        TextEntry::make('marcaCatalogo.nome')
                            ->label('Marca')
                            ->weight(FontWeight::Bold)
                            ->columnSpan(2),
                        TextEntry::make('modeloCatalogo.nome')
                            ->label('Modelo Carcaça')
                            ->weight(FontWeight::Bold)
                            ->columnSpan(2),
                        TextEntry::make('medidaCatalogo.codigo')
                            ->label('Medida Carcaça')
                            ->weight(FontWeight::Bold)
                            ->columnSpan(2),
                        TextEntry::make('ultimoRecap.desenhoPneu.descricao')
                            ->label('Borracha Recap')
                            ->weight(FontWeight::Bold)
                            ->placeholder('Não Recapado')
                            ->columnSpan(2),
                        TextEntry::make('veiculo.placa')
                            ->label('Veículo')
                            ->weight(FontWeight::Bold)
                            ->columnSpan(2),
                        TextEntry::make('km_percorrido_ciclo')
                            ->label('KM Percorrido no Ciclo')
                            ->weight(FontWeight::Bold)
                            ->numeric(0, ',', '.')
                            ->columnSpan(2),
                        TextEntry::make('km_percorrido')
                            ->label('KM Percorrido Total')
                            ->numeric(0, ',', '.')
                            ->weight(FontWeight::Bold)
                            ->columnSpan(2),
                        TextEntry::make('status')
                            ->label('Status')
                            ->weight(FontWeight::Bold)
                            ->color('info')
                            ->columnSpan(2),
                        TextEntry::make('localCatalogo.nome')
                            ->label('Local')
                            ->weight(FontWeight::Bold)
                            ->columnSpan(2),
                        TextEntry::make('numero_serie')
                            ->label('Nº Série')
                            ->columnSpan(2),
                        TextEntry::make('dot')
                            ->label('DOT')
                            ->columnSpan(2),
                        TextEntry::make('nota_fiscal')
                            ->label('Nota Fiscal')
                            ->columnSpan(2),
                        TextEntry::make('fornecedorCompra.nome')
                            ->label('Fornecedor Compra')
                            ->placeholder('Não informado')
                            ->columnSpan(2),
                        TextEntry::make('sulco_inicial')
                            ->label('Sulco Inicial')
                            ->numeric(2, ',', '.')
                            ->columnSpan(2),
                        TextEntry::make('limite_recapagens')
                            ->label('Limite Recapagens')
                            ->columnSpan(2),
                    ]),
                Section::make('Informações de Ult. Movimentação')
                    ->columns(12)
                    ->columnSpan(12)
                    ->components([
                        TextEntry::make('veiculo')
                            ->label('Veículo')
                            ->state(
                                fn (Models\Pneu $record) => $record->historicoMovimentacao()
                                    ->latest()
                                    ->first()?->veiculo?->placa ?? 'Não informado'
                            )
                            ->columnSpan(2),
                        TextEntry::make('ciclo_vida')
                            ->label('Ciclo de Vida')
                            ->state(
                                fn (Models\Pneu $record) => $record->historicoMovimentacao()
                                    ->latest()
                                    ->value('ciclo_vida')
                            )

                            ->placeholder('Não informado')
                            ->columnSpan(2),
                        TextEntry::make('posicao_eixo')
                            ->label('Posição/Eixo')
                            ->state(function (Models\Pneu $record) {
                                $ultMov = $record->historicoMovimentacao()->latest();

                                return $ultMov->exists() ? $ultMov->value('eixo').'° Eixo '.$ultMov->value('posicao') : 'Não informado';
                            })

                            ->placeholder('Não informado')
                            ->columnSpan(2),
                        TextEntry::make('data_inicial')
                            ->label('Dt. Aplicação')
                            ->state(
                                fn (Models\Pneu $record) => $record->historicoMovimentacao()
                                    ->latest()
                                    ->value('data_inicial')
                            )
                            ->date('d/m/Y')
                            ->placeholder('Não informado')
                            ->columnSpan(2),
                        TextEntry::make('data_final')
                            ->label('Dt. Remoção')
                            ->state(
                                fn (Models\Pneu $record) => $record->historicoMovimentacao()
                                    ->latest()
                                    ->value('data_final')
                            )
                            ->date('d/m/Y')
                            ->placeholder('Não informado')
                            ->columnSpan(2),
                        TextEntry::make('km_percorrido')
                            ->label('Km Percorrido')
                            ->state(
                                fn (Models\Pneu $record) => $record->historicoMovimentacao()
                                    ->latest()
                                    ->value('km_percorrido')
                            )
                            ->numeric(0, ',', '.')
                            ->columnSpan(2),
                        TextEntry::make('motivo')
                            ->label('Motivo Remoção')
                            ->state(
                                fn (Models\Pneu $record) => $record->historicoMovimentacao()
                                    ->latest()
                                    ->value('motivo')
                            )
                            ->columnSpan(2),
                        TextEntry::make('observacao')
                            ->label('Observação')
                            ->html()
                            ->state(
                                fn (Models\Pneu $record) => $record->historicoMovimentacao()
                                    ->latest()
                                    ->value('observacao')
                            )
                            ->placeholder('Não informado')
                            ->columnSpan(6),
                    ]),
                CiclosVidaSection::infolist(),
            ]);
    }
}
