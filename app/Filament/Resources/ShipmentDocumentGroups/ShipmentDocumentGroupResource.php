<?php

namespace App\Filament\Resources\ShipmentDocumentGroups;

use App\Filament\Resources\ShipmentDocumentGroups\Pages\ListShipmentDocumentGroups;
use App\Filament\Resources\ShipmentDocumentGroups\Pages\ViewShipmentDocumentGroup;
use App\Filament\Resources\ShipmentDocumentGroups\Schemas\ShipmentDocumentGroupInfolist;
use App\Filament\Resources\ShipmentDocumentGroups\Tables\ShipmentDocumentGroupsTable;
use App\Models\ShipmentDocumentGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ShipmentDocumentGroupResource extends Resource
{
    protected static ?string $model = ShipmentDocumentGroup::class;

    protected static string|UnitEnum|null $navigationGroup = 'Automacoes';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static ?string $modelLabel = 'Grupo de Notas';

    protected static ?string $pluralModelLabel = 'Grupos de Notas';

    public static function infolist(Schema $schema): Schema
    {
        return ShipmentDocumentGroupInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShipmentDocumentGroupsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShipmentDocumentGroups::route('/'),
            'view' => ViewShipmentDocumentGroup::route('/{record}'),
        ];
    }
}
