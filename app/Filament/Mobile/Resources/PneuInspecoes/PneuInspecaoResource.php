<?php

namespace App\Filament\Mobile\Resources\PneuInspecoes;

use App\Filament\Mobile\Resources\PneuInspecoes\Pages\MobileListPneuInspecoes;
use App\Models\PneuInspecao;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;

class PneuInspecaoResource extends Resource
{
    protected static ?string $model = PneuInspecao::class;

    protected static ?string $slug = 'pneu-inspecoes';

    protected static string|UnitEnum|null $navigationGroup = 'Pneus';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static ?string $modelLabel = 'Inspeção de Pneu';

    protected static ?string $pluralModelLabel = 'Inspeções de Pneu';

    protected static ?int $navigationSort = 20;

    public static function getPages(): array
    {
        return [
            'index' => MobileListPneuInspecoes::route('/'),
        ];
    }
}
