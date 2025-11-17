<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class DashboardOficina extends Page
{
    protected string $view = 'filament.pages.dashboard-oficina';

    protected static ?string $title = 'Dashboard Oficina';

    protected static ?string $navigationLabel = 'Dashboard Oficina';

    protected static string|UnitEnum|null $navigationGroup = 'Manutenção';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    public function getHeaderWidgetsColumns(): int | array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
        ];
    }
}
