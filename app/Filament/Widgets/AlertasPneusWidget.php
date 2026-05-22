<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Veiculos\VeiculoResource;
use App\Services\Pneus\PneuAlertaService;
use Filament\Widgets\Widget;

class AlertasPneusWidget extends Widget
{
    protected string $view = 'filament.widgets.alertas-pneus-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public ?string $veiculoIdFilter = null;

    public ?string $eixoFilter = null;

    public ?string $posicaoFilter = null;

    public function resetFilters(): void
    {
        $this->veiculoIdFilter = null;
        $this->eixoFilter = null;
        $this->posicaoFilter = null;
    }

    public function getViewData(): array
    {
        $service = app(PneuAlertaService::class);
        $filters = [
            'veiculo_id' => $this->veiculoIdFilter,
            'eixo' => $this->eixoFilter,
            'posicao' => $this->posicaoFilter,
        ];
        $data = $service->getDashboardData($filters);
        $options = $service->getFilterOptions();

        return [
            ...$data,
            ...$options,
            'filters' => $filters,
            'veiculoUrl' => fn (?int $veiculoId) => $veiculoId
                ? VeiculoResource::getUrl('edit', ['record' => $veiculoId])
                : null,
        ];
    }
}
