<?php

namespace App\Filament\Resources\Viagems;

use App\Filament\Resources\Viagems\Pages\CreateViagem;
use App\Filament\Resources\Viagems\Pages\EditViagem;
use App\Filament\Resources\Viagems\Pages\ListViagems;
use App\Filament\Resources\Viagems\Pages\ViewViagem;
use App\Filament\Resources\Viagems\Schemas\ViagemForm;
use App\Filament\Resources\Viagems\Tables\ViagemsTable;
use App\Models\Viagem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ViagemResource extends Resource
{
    protected static ?string $model = Viagem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Viagens';

    protected static ?string $modelLabel = 'Viagem';

    protected static ?string $pluralModelLabel = 'Viagens';

    protected static ?string $recordTitleAttribute = 'numero_viagem';

    public static function form(Schema $schema): Schema
    {
        return ViagemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ViagemsTable::configure($table);
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
            'index' => ListViagems::route('/'),
            'edit' => EditViagem::route('/{record}/edit'),
            'view' => ViewViagem::route('/{record}'),
        ];
    }

}
