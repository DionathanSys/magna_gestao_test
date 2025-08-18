<?php

namespace App\Filament\Resources\Viagems;

use App\Filament\Resources\Viagems\Pages\CreateViagem;
use App\Filament\Resources\Viagems\Pages\EditViagem;
use App\Filament\Resources\Viagems\Pages\ListViagems;
use App\Filament\Resources\Viagems\Schemas\ViagemForm;
use App\Filament\Resources\Viagems\Tables\ViagemsTable;
use App\Models\Viagem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ViagemResource extends Resource
{
    protected static ?string $model = Viagem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

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
            'create' => CreateViagem::route('/create'),
            // 'edit' => EditViagem::route('/{record}/edit'),
        ];
    }
}
