<?php

namespace App\Filament\Resources\CargaViagems;

use App\Filament\Resources\CargaViagems\Pages\CreateCargaViagem;
use App\Filament\Resources\CargaViagems\Pages\EditCargaViagem;
use App\Filament\Resources\CargaViagems\Pages\ListCargaViagems;
use App\Filament\Resources\CargaViagems\Schemas\CargaViagemForm;
use App\Filament\Resources\CargaViagems\Tables\CargaViagemsTable;
use BackedEnum;
use App\Models\CargaViagem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CargaViagemResource extends Resource
{
    protected static ?string $model = CargaViagem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Viagens';

    protected static ?string $modelLabel = 'Carga Viagem';

    protected static ?string $pluralModelLabel = 'Cargas Viagem';

    protected static ?string $recordTitleAttribute = 'numero_viagem';

    public static function form(Schema $schema): Schema
    {
        return CargaViagemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CargaViagemsTable::configure($table);
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
            'index' => ListCargaViagems::route('/'),
            'create' => CreateCargaViagem::route('/create'),
            'edit' => EditCargaViagem::route('/{record}/edit'),
        ];
    }
}
