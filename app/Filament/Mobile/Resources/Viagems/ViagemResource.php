<?php

namespace App\Filament\Mobile\Resources\Viagems;

use App\Filament\Mobile\Resources\Viagems\Pages\MobileListViagems;
use App\Models\Viagem;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;

class ViagemResource extends Resource
{
    protected static ?string $model = Viagem::class;

    protected static string|UnitEnum|null $navigationGroup = 'Viagens';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map';

    protected static ?string $modelLabel = 'Viagem';

    protected static ?string $pluralModelLabel = 'Viagens';

    protected static ?string $recordTitleAttribute = 'numero_viagem';

    protected static ?string $slug = 'viagens';

    public static function getPages(): array
    {
        return [
            'index' => MobileListViagems::route('/'),
        ];
    }
}
