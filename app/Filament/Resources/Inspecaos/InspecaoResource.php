<?php

namespace App\Filament\Resources\Inspecaos;

use App\Filament\Resources\Inspecaos\Pages\CreateInspecao;
use App\Filament\Resources\Inspecaos\Pages\EditInspecao;
use App\Filament\Resources\Inspecaos\Pages\ListInspecaos;
use App\Filament\Resources\Inspecaos\Pages\ViewInspecao;
use App\Filament\Resources\Inspecaos\RelationManagers\ItensRelationManager;
use App\Filament\Resources\Inspecaos\Schemas\InspecaoForm;
use App\Filament\Resources\Inspecaos\Schemas\InspecaoInfolist;
use App\Filament\Resources\Inspecaos\Tables\InspecaosTable;
use App\Models\Inspecao;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class InspecaoResource extends Resource
{
    protected static ?string $model = Inspecao::class;

    protected static string|UnitEnum|null $navigationGroup = 'Inspeções';

    protected static ?string $modelLabel = 'Inspeções';

    protected static ?string $pluralModelLabel = 'Inspeções';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return InspecaoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InspecaoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InspecaosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItensRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInspecaos::route('/'),
            'create' => CreateInspecao::route('/create'),
            'view' => ViewInspecao::route('/{record}'),
            'edit' => EditInspecao::route('/{record}/edit'),
        ];
    }
}
