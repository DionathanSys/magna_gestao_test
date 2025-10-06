<?php

namespace App\Filament\Resources\ItemInspecaos;

use App\Filament\Resources\ItemInspecaos\Pages\CreateItemInspecao;
use App\Filament\Resources\ItemInspecaos\Pages\EditItemInspecao;
use App\Filament\Resources\ItemInspecaos\Pages\ListItemInspecaos;
use App\Filament\Resources\ItemInspecaos\Pages\ViewItemInspecao;
use App\Filament\Resources\ItemInspecaos\Schemas\ItemInspecaoForm;
use App\Filament\Resources\ItemInspecaos\Schemas\ItemInspecaoInfolist;
use App\Filament\Resources\ItemInspecaos\Tables\ItemInspecaosTable;
use App\Models\ItemInspecao;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ItemInspecaoResource extends Resource
{
    protected static ?string $model = ItemInspecao::class;

    protected static string|UnitEnum|null $navigationGroup = 'Inspeções';

    protected static ?string $modelLabel = 'Itens de Inspeção';

    protected static ?string $pluralModelLabel = 'Itens de Inspeção';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return ItemInspecaoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ItemInspecaoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ItemInspecaosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListItemInspecaos::route('/'),
            'create' => CreateItemInspecao::route('/create'),
            'view' => ViewItemInspecao::route('/{record}'),
            'edit' => EditItemInspecao::route('/{record}/edit'),
        ];
    }
}
