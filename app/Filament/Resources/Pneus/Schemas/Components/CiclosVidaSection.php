<?php

namespace App\Filament\Resources\Pneus\Schemas\Components;

use App\Enum\Pneu\StatusCicloPneuEnum;
use App\Filament\Resources\DesenhoPneus\DesenhoPneuResource;
use App\Models\Pneu;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn as RepeaterTableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn as InfolistTableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CiclosVidaSection
{
    public static function infolist(): Section
    {
        return Section::make('Histórico de Ciclos de Vida')
            ->description('Resumo dos ciclos da carcaça, com desenho, datas, quilometragem e vínculos operacionais.')
            ->columns(12)
            ->columnSpanFull()
            ->components([
                RepeatableEntry::make('ciclos')
                    ->label('Ciclos')
                    ->columnSpanFull()
                    ->placeholder('Nenhum ciclo de vida registrado.')
                    ->state(fn (Pneu $record): array => $record->ciclos()
                        ->with('desenhoPneu')
                        ->withCount(['recapagens', 'consertos', 'inspecoes'])
                        ->orderByDesc('numero')
                        ->get()
                        ->map(fn ($ciclo): array => [
                            'numero' => $ciclo->numero,
                            'status' => $ciclo->status?->value,
                            'desenho' => $ciclo->desenhoPneu?->descricao,
                            'data_abertura' => $ciclo->data_abertura,
                            'data_fechamento' => $ciclo->data_fechamento,
                            'km_inicial' => $ciclo->km_inicial,
                            'km_final' => $ciclo->km_final,
                            'recapagens_count' => $ciclo->recapagens_count,
                            'consertos_count' => $ciclo->consertos_count,
                            'inspecoes_count' => $ciclo->inspecoes_count,
                            'observacao' => $ciclo->observacao,
                        ])
                        ->all())
                    ->table([
                        InfolistTableColumn::make('Ciclo'),
                        InfolistTableColumn::make('Status'),
                        InfolistTableColumn::make('Desenho')->width('18%'),
                        InfolistTableColumn::make('Abertura'),
                        InfolistTableColumn::make('Fechamento'),
                        InfolistTableColumn::make('KM Inicial'),
                        InfolistTableColumn::make('KM Final'),
                        InfolistTableColumn::make('Recap.'),
                        InfolistTableColumn::make('Consertos'),
                        InfolistTableColumn::make('Inspeções'),
                        InfolistTableColumn::make('Observação')->width('20%'),
                    ])
                    ->schema([
                        TextEntry::make('numero')
                            ->formatStateUsing(fn ($state): string => 'Ciclo '.$state),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (?string $state): string => $state === StatusCicloPneuEnum::ABERTO->value ? 'success' : 'gray'),
                        TextEntry::make('desenho')
                            ->placeholder('Sem desenho'),
                        TextEntry::make('data_abertura')
                            ->date('d/m/Y')
                            ->placeholder('Não informado'),
                        TextEntry::make('data_fechamento')
                            ->date('d/m/Y')
                            ->placeholder('Em aberto'),
                        TextEntry::make('km_inicial')
                            ->numeric(0, ',', '.')
                            ->placeholder('Não informado'),
                        TextEntry::make('km_final')
                            ->numeric(0, ',', '.')
                            ->placeholder('Em aberto'),
                        TextEntry::make('recapagens_count')
                            ->numeric(0, ',', '.'),
                        TextEntry::make('consertos_count')
                            ->numeric(0, ',', '.'),
                        TextEntry::make('inspecoes_count')
                            ->numeric(0, ',', '.'),
                        TextEntry::make('observacao')
                            ->placeholder('Sem observação'),
                    ]),
            ]);
    }

    public static function form(): Section
    {
        return Section::make('Ciclos de Vida')
            ->description('Edite os dados cadastrais dos ciclos. Recapagens, consertos e inspeções continuam nos relacionamentos próprios.')
            ->columns(12)
            ->columnSpanFull()
            ->schema([
                Repeater::make('ciclos')
                    ->hiddenLabel()
                    ->relationship('ciclos', fn ($query) => $query->orderByDesc('numero'))
                    ->columns(12)
                    ->columnSpanFull()
                    ->defaultItems(0)
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->table([
                        RepeaterTableColumn::make('Ciclo'),
                        RepeaterTableColumn::make('Status'),
                        RepeaterTableColumn::make('Desenho Borracha')->width('18%'),
                        RepeaterTableColumn::make('Dt. Abertura'),
                        RepeaterTableColumn::make('Dt. Fechamento'),
                        RepeaterTableColumn::make('KM Inicial'),
                        RepeaterTableColumn::make('KM Final'),
                        RepeaterTableColumn::make('Observação')->width('22%'),
                    ])
                    ->compact()
                    ->itemLabel(fn (array $state): string => 'Ciclo '.($state['numero'] ?? '-'))
                    ->schema([
                        TextInput::make('numero')
                            ->label('Ciclo')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->columnSpan(2),
                        Select::make('status')
                            ->native(false)
                            ->options(StatusCicloPneuEnum::toSelectArray())
                            ->required()
                            ->columnSpan(3),
                        Select::make('desenho_pneu_id')
                            ->label('Desenho Borracha')
                            ->relationship('desenhoPneu', 'descricao', fn ($query) => $query->where('ativo', true))
                            ->createOptionForm(fn (Schema $schema) => DesenhoPneuResource::form($schema))
                            ->preload()
                            ->searchable()
                            ->columnSpan(7),
                        DatePicker::make('data_abertura')
                            ->label('Dt. Abertura')
                            ->date('d/m/Y')
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection()
                            ->columnSpan(3),
                        DatePicker::make('data_fechamento')
                            ->label('Dt. Fechamento')
                            ->date('d/m/Y')
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection()
                            ->columnSpan(3),
                        TextInput::make('km_inicial')
                            ->label('KM Inicial')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(3),
                        TextInput::make('km_final')
                            ->label('KM Final')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(3),
                        Textarea::make('observacao')
                            ->label('Observação')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
