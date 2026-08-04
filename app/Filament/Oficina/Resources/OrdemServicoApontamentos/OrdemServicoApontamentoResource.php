<?php

namespace App\Filament\Oficina\Resources\OrdemServicoApontamentos;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Filament\Oficina\Resources\OrdemServicoApontamentos\Pages\ListOrdemServicoApontamentos;
use App\Filament\Oficina\Resources\OrdemServicos\OrdemServicoResource;
use App\Models\OrdemServicoApontamento;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrdemServicoApontamentoResource extends Resource
{
    protected static ?string $model = OrdemServicoApontamento::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Apontamentos';

    protected static ?string $modelLabel = 'Apontamento de OS';

    protected static ?string $pluralModelLabel = 'Apontamentos de OS';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'ordemServico.veiculo:id,placa',
                'ordemServico:id,veiculo_id,status',
                'colaborador:id,codigo,nome',
                'itens.servico:id,codigo,descricao',
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('iniciado_em', 'desc')
            ->columns([
                TextColumn::make('ordem_servico_id')
                    ->label('OS')
                    ->sortable()
                    ->url(fn (OrdemServicoApontamento $record): string => OrdemServicoResource::getUrl('view', ['record' => $record->ordem_servico_id])),
                TextColumn::make('ordemServico.veiculo.placa')
                    ->label('Veículo')
                    ->badge()
                    ->searchable(),
                TextColumn::make('ordemServico.status')
                    ->label('Status OS')
                    ->badge(),
                TextColumn::make('colaborador.nome')
                    ->label('Responsável')
                    ->searchable()
                    ->description(fn (OrdemServicoApontamento $record): ?string => $record->colaborador?->codigo),
                TextColumn::make('iniciado_em')
                    ->label('Início')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('encerrado_em')
                    ->label('Fim')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Em aberto')
                    ->sortable(),
                TextColumn::make('duracao')
                    ->label('Duração')
                    ->state(fn (OrdemServicoApontamento $record): string => $record->encerrado_em
                        ? $record->iniciado_em->diffForHumans($record->encerrado_em, true)
                        : $record->iniciado_em->diffForHumans(now(), true)),
                TextColumn::make('situacao')
                    ->label('Situação')
                    ->badge()
                    ->state(fn (OrdemServicoApontamento $record): string => $record->encerrado_em ? 'Encerrado' : 'Em execução')
                    ->color(fn (string $state): string => $state === 'Em execução' ? 'info' : 'success'),
                TextColumn::make('itens.servico.descricao')
                    ->label('Serviços executados')
                    ->listWithLineBreaks()
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('situacao')
                    ->label('Situação')
                    ->options([
                        'aberto' => 'Em execução',
                        'encerrado' => 'Encerrado',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(($data['value'] ?? null) === 'aberto', fn (Builder $query) => $query->whereNull('encerrado_em'))
                        ->when(($data['value'] ?? null) === 'encerrado', fn (Builder $query) => $query->whereNotNull('encerrado_em'))),
                SelectFilter::make('status_os')
                    ->label('Status OS')
                    ->options(StatusOrdemServicoEnum::toSelectArray())
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, fn (Builder $query, string $status) => $query
                            ->whereHas('ordemServico', fn (Builder $query) => $query->where('status', $status)))),
                SelectFilter::make('colaborador_id')
                    ->label('Responsável')
                    ->relationship('colaborador', 'nome')
                    ->searchable()
                    ->preload(),
                Filter::make('iniciado_em')
                    ->form([
                        DatePicker::make('data_inicio')->label('Início de'),
                        DatePicker::make('data_fim')->label('Início até'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['data_inicio'] ?? null, fn (Builder $query, $date) => $query->whereDate('iniciado_em', '>=', $date))
                        ->when($data['data_fim'] ?? null, fn (Builder $query, $date) => $query->whereDate('iniciado_em', '<=', $date))),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
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
            'index' => ListOrdemServicoApontamentos::route('/'),
        ];
    }
}
