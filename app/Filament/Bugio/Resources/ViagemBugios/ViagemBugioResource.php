<?php

namespace App\Filament\Bugio\Resources\ViagemBugios;

use App\Filament\Bugio\Resources\ViagemBugios\Pages\CreateViagemBugio;
use App\Filament\Bugio\Resources\ViagemBugios\Pages\EditViagemBugio;
use App\Filament\Bugio\Resources\ViagemBugios\Pages\ListViagemBugios;
use App\Filament\Bugio\Resources\ViagemBugios\Pages\ViewViagemBugio;
use App\Filament\Bugio\Resources\ViagemBugios\Schemas\ViagemBugioForm;
use App\Filament\Bugio\Resources\ViagemBugios\Schemas\ViagemBugioInfolist;
use App\Filament\Bugio\Resources\ViagemBugios\Tables\ViagemBugiosTable;
use App\Models\ViagemBugio;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ViagemBugioResource extends Resource
{
    protected static ?string $model = ViagemBugio::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Map;

    protected static ?string $modelLabel = 'Viagem';

    protected static ?string $pluralModelLabel = 'Viagens';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return ViagemBugioForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ViagemBugioInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ViagemBugiosTable::configure($table);
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
            'index' => ListViagemBugios::route('/'),
            'create' => CreateViagemBugio::route('/create'),
            'view' => ViewViagemBugio::route('/{record}'),
            'edit' => EditViagemBugio::route('/{record}/edit'),
        ];
    }
}
