<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AlertasPneusWidget;
use App\Filament\Widgets\KmRodadoPneu;
use App\Filament\Widgets\KmVeiculoDesatualizado;
use App\Services\Pneus\RelatorioMovimentacoesPneusPdfService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class DashboardPneus extends BaseDashboard
{
    protected static string|UnitEnum|null $navigationGroup = 'Pneus';

    public static function getNavigationIcon(): string | BackedEnum | Htmlable | null
    {
        return null;
    }

    protected static string $routePath = 'dashboard-pneus';

    protected static ?string $title = 'Dashboard Pneus';

    protected static ?int $navigationSort = -2;

    public function getColumns(): int | array
    {
        return 2;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('relatorioMovimentacoesPneusPdf')
                ->label('Relatório PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->form([
                    DatePicker::make('data_inicial')
                        ->label('Data inicial')
                        ->default(now()->startOfMonth())
                        ->maxDate(now())
                        ->required(),
                    DatePicker::make('data_final')
                        ->label('Data final')
                        ->default(now())
                        ->maxDate(now())
                        ->required(),
                ])
                ->modalDescription('Gera um PDF com todas as movimentações de pneus no período, agrupadas por veículo, usando a data de criação do registro.')
                ->action(function (array $data) {
                    if ($data['data_final'] < $data['data_inicial']) {
                        Notification::make()
                            ->title('A data final não pode ser menor que a data inicial.')
                            ->warning()
                            ->send();

                        return null;
                    }

                    $service = app(RelatorioMovimentacoesPneusPdfService::class);
                    $movimentacoes = $service->getMovimentacoes($data['data_inicial'], $data['data_final']);

                    if ($movimentacoes->isEmpty()) {
                        Notification::make()
                            ->title('Nenhuma movimentação encontrada para o período informado.')
                            ->warning()
                            ->send();

                        return null;
                    }

                    return $service->gerarPdf($data['data_inicial'], $data['data_final']);
                }),
        ];
    }

    public function getWidgets(): array
    {
        return [
            KmVeiculoDesatualizado::class,
            AlertasPneusWidget::class,
            KmRodadoPneu::class,
        ];
    }

}
