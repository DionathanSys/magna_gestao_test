<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Veiculos\VeiculoResource;
use App\Services\Pneus\PneuAlertaService;
use Filament\Widgets\Widget;

class AlertasPneusWidget extends Widget
{
    protected string $view = 'filament.widgets.alertas-pneus-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function getViewData(): array
    {
        $service = app(PneuAlertaService::class);
        $data = $service->getDashboardData();

        return [
            ...$data,
            'veiculoUrl' => fn (?int $veiculoId) => $veiculoId
                ? VeiculoResource::getUrl('edit', ['record' => $veiculoId])
                : null,
        ];
    }
}
