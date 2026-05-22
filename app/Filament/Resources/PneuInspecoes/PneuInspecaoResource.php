<?php

namespace App\Filament\Resources\PneuInspecoes;

use App\Filament\Resources\PneuInspecoes\Pages\CreatePneuInspecao;
use App\Filament\Resources\PneuInspecoes\Pages\EditPneuInspecao;
use App\Filament\Resources\PneuInspecoes\Pages\ListPneuInspecoes;
use App\Filament\Resources\PneuInspecoes\Schemas\PneuInspecaoForm;
use App\Filament\Resources\PneuInspecoes\Schemas\PneuInspecaoInfolist;
use App\Filament\Resources\PneuInspecoes\Tables\PneuInspecoesTable;
use App\Models\PneuInspecao;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PneuInspecaoResource extends Resource
{
    protected static ?string $model = PneuInspecao::class;

    protected static ?string $slug = 'pneu-inspecoes';

    protected static string|UnitEnum|null $navigationGroup = 'Pneus';

    protected static ?string $modelLabel = 'Inspeção de Pneu';

    protected static ?string $pluralModelLabel = 'Inspeções de Pneu';

    protected static ?int $navigationSort = 14;

    public static function form(Schema $schema): Schema
    {
        return PneuInspecaoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PneuInspecaoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PneuInspecoesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPneuInspecoes::route('/'),
            'create' => CreatePneuInspecao::route('/create'),
            'edit' => EditPneuInspecao::route('/{record}/edit'),
        ];
    }
}
