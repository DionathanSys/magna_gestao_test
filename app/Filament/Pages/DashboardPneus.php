<?php

namespace App\Filament\Pages;

use BackedEnum;
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
}
