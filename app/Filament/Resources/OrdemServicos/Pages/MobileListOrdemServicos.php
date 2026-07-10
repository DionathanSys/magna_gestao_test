<?php

namespace App\Filament\Resources\OrdemServicos\Pages;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Models\OrdemServico;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

class MobileListOrdemServicos extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = OrdemServicoResource::class;

    protected static ?string $title = 'Ordens de Serviço';

    protected static string|UnitEnum|null $navigationGroup = 'Manutenção';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.resources.ordem-servicos.pages.mobile-list';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OrdemServico::query()
                    ->with(['veiculo:id,placa', 'itens.servico:id,descricao'])
                    ->where('status', '!=', StatusOrdemServicoEnum::CANCELADO)
                    ->orderByDesc('id')
            )
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->weight('bold')
                    ->width('50px'),
                TextColumn::make('veiculo.placa')
                    ->label('Placa')
                    ->weight('bold')
                    ->searchable()
                    ->width('80px'),
                TextColumn::make('itensCount')
                    ->label('Serviços')
                    ->getStateUsing(fn (OrdemServico $record): string => $record->itens->count())
                    ->badge()
                    ->color('gray')
                    ->width('60px'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (StatusOrdemServicoEnum $state): string => match ($state) {
                        StatusOrdemServicoEnum::PENDENTE => 'warning',
                        StatusOrdemServicoEnum::EXECUCAO => 'info',
                        StatusOrdemServicoEnum::CONCLUIDO => 'success',
                        default => 'gray',
                    })
                    ->width('100px'),
                TextColumn::make('data_inicio')
                    ->label('Abertura')
                    ->dateTime('d/m')
                    ->width('50px'),
                TextColumn::make('tipo_manutencao')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'Corretiva' => 'danger',
                        'Preventiva' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordUrl(fn (OrdemServico $record): string => static::getUrl('mobile-detail', ['record' => $record->id]))
            ->recordClasses('cursor-pointer')
            ->paginated([15, 30, 50])
            ->defaultPaginationPageOption(15);
    }

    protected function getTableHeaderActions(): array
    {
        return [
            Action::make('nova-os')
                ->label('Nova OS')
                ->icon('heroicon-o-plus')
                ->url(static::getUrl('mobile-create')),
        ];
    }
}
