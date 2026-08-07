<?php

namespace App\Filament\Oficina\Resources\OrdemServicos;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Filament\Oficina\Resources\OrdemServicos\Pages\EditOrdemServico;
use App\Filament\Oficina\Resources\OrdemServicos\Pages\ListOrdemServicos;
use App\Filament\Oficina\Resources\OrdemServicos\Pages\ViewOrdemServico;
use App\Filament\Oficina\Resources\OrdemServicos\Tables\OrdemServicosTable;
use App\Models\ItemOrdemServico;
use App\Models\OrdemServico;
use BackedEnum;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OrdemServicoResource extends Resource
{
    protected static ?string $model = OrdemServico::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $modelLabel = 'Ordem de Serviço';

    protected static ?string $pluralModelLabel = 'Ordens de Serviço';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNull('parceiro_id')
            ->with([
                'veiculo',
                'itens.servico',
                'apontamentosAbertosOficina.colaborador',
                'apontamentosOficina.colaborador',
                'apontamentosOficina.itens.servico',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ordem de Serviço')
                ->columns(4)
                ->schema([
                    Select::make('veiculo_id')
                        ->label('Veículo')
                        ->relationship('veiculo', 'placa')
                        ->disabled(fn (): bool => ! Auth::user()->is_admin),
                    TextInput::make('quilometragem')
                        ->label('Quilometragem')
                        ->numeric()
                        ->disabled(fn (): bool => ! Auth::user()->is_admin),
                    Select::make('status')
                        ->label('Status')
                        ->options(StatusOrdemServicoEnum::toSelectArray())
                        ->disabled(fn (): bool => ! Auth::user()->is_admin),
                    DateTimePicker::make('data_inicio')
                        ->label('Data de Abertura')
                        ->seconds(false)
                        ->disabled(fn (): bool => ! Auth::user()->is_admin),
                    Toggle::make('veiculo_na_oficina')
                        ->label('Veículo na oficina')
                        ->disabled(fn (): bool => ! Auth::user()->is_admin),
                ]),
            Section::make('Serviços')
                ->schema([
                    Repeater::make('itens')
                        ->label('')
                        ->relationship('itens')
                        ->columns(5)
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false)
                        ->schema([
                            TextInput::make('servico_codigo')
                                ->label('Código')
                                ->disabled()
                                ->dehydrated(false),
                            TextInput::make('servico_nome')
                                ->label('Serviço')
                                ->columnSpan(2)
                                ->disabled()
                                ->dehydrated(false),
                            TextInput::make('posicao')
                                ->label('Posição')
                                ->disabled(fn (): bool => ! Auth::user()->is_admin),
                            Select::make('status')
                                ->label('Status')
                                ->options(StatusOrdemServicoEnum::toSelectArray())
                                ->disabled(fn (): bool => ! Auth::user()->is_admin),
                        ]),
                ]),
            Section::make('Histórico de Trabalho')
                ->schema([
                    Repeater::make('apontamentosOficina')
                        ->label('')
                        ->relationship('apontamentosOficina')
                        ->columns(4)
                        ->addable(false)
                        ->deletable(fn (): bool => Auth::user()->is_admin)
                        ->reorderable(false)
                        ->schema([
                            Select::make('colaborador_id')
                                ->label('Colaborador')
                                ->relationship('colaborador', 'nome')
                                ->searchable()
                                ->preload()
                                ->disabled(fn (): bool => ! Auth::user()->is_admin),
                            DateTimePicker::make('iniciado_em')
                                ->label('Início')
                                ->seconds(false)
                                ->disabled(fn (): bool => ! Auth::user()->is_admin),
                            DateTimePicker::make('encerrado_em')
                                ->label('Fim')
                                ->seconds(false)
                                ->disabled(fn (): bool => ! Auth::user()->is_admin),
                            TextInput::make('servicos_executados')
                                ->label('Serviços')
                                ->formatStateUsing(fn ($record): string => $record?->itens
                                    ? $record->itens->map(fn (ItemOrdemServico $item): string => self::servicoItemLabel($item))->filter()->join(', ')
                                    : '')
                                ->disabled()
                                ->dehydrated(false),
                        ]),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dados da OS')
                ->columns(4)
                ->schema([
                    TextEntry::make('id')->label('OS'),
                    TextEntry::make('veiculo.placa')->label('Veículo')->badge(),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('veiculo_na_oficina')
                        ->label('Oficina')
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Na oficina' : 'Fora da oficina')
                        ->badge()
                        ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                    TextEntry::make('data_inicio')->label('Abertura')->dateTime('d/m/Y H:i'),
                ]),
            Section::make('Trabalhando Agora')
                ->schema([
                    RepeatableEntry::make('apontamentosAbertosOficina')
                        ->label('')
                        ->table([
                            TableColumn::make('Código'),
                            TableColumn::make('Colaborador'),
                            TableColumn::make('Início'),
                            TableColumn::make('Tempo'),
                        ])
                        ->schema([
                            TextEntry::make('colaborador.codigo')->label('Código'),
                            TextEntry::make('colaborador.nome')->label('Colaborador'),
                            TextEntry::make('iniciado_em')->label('Início')->dateTime('d/m/Y H:i'),
                            TextEntry::make('tempo_atual')
                                ->label('Tempo')
                                ->state(fn ($record): string => $record->iniciado_em?->diffForHumans(now(), true) ?? '-'),
                        ]),
                ]),
            Section::make('Serviços')
                ->schema([
                    RepeatableEntry::make('itens')
                        ->label('')
                        ->table([
                            TableColumn::make('Código'),
                            TableColumn::make('Serviço'),
                            TableColumn::make('Posição'),
                            TableColumn::make('Status'),
                        ])
                        ->schema([
                            TextEntry::make('servico.codigo')->label('Código'),
                            TextEntry::make('servico.descricao')->label('Serviço'),
                            TextEntry::make('posicao')->label('Posição')->placeholder('-'),
                            TextEntry::make('status')->label('Status')->badge(),
                        ]),
                ]),
            Section::make('Histórico')
                ->schema([
                    RepeatableEntry::make('apontamentosOficina')
                        ->label('')
                        ->table([
                            TableColumn::make('Colaborador'),
                            TableColumn::make('Início'),
                            TableColumn::make('Fim'),
                            TableColumn::make('Duração'),
                            TableColumn::make('Serviços'),
                        ])
                        ->schema([
                            TextEntry::make('colaborador.nome')->label('Colaborador'),
                            TextEntry::make('iniciado_em')->label('Início')->dateTime('d/m/Y H:i'),
                            TextEntry::make('encerrado_em')->label('Fim')->dateTime('d/m/Y H:i')->placeholder('Aberto'),
                            TextEntry::make('duracao')
                                ->label('Duração')
                                ->state(fn ($record): string => $record->encerrado_em
                                    ? $record->iniciado_em->diffForHumans($record->encerrado_em, true)
                                    : '-'),
                            TextEntry::make('servicos_executados')
                                ->label('Serviços')
                                ->state(fn ($record): array => $record->itens
                                    ->map(fn (ItemOrdemServico $item): string => self::servicoItemLabel($item))
                                    ->filter()
                                    ->values()
                                    ->all() ?: ['-'])
                                ->listWithLineBreaks()
                                ->bulleted(false),
                        ]),
                ]),
        ]);
    }

    private static function servicoItemLabel(ItemOrdemServico $item): string
    {
        $label = trim(($item->servico?->codigo ? $item->servico->codigo.' - ' : '').($item->servico?->descricao ?? 'Serviço não informado'));

        if (filled($item->posicao)) {
            $label .= ' | Posição: '.$item->posicao;
        }

        return $label;
    }

    public static function table(Table $table): Table
    {
        return OrdemServicosTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrdemServicos::route('/'),
            'view' => ViewOrdemServico::route('/{record}'),
            'edit' => EditOrdemServico::route('/{record}/edit'),
        ];
    }
}
