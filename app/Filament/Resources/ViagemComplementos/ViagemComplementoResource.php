<?php

namespace App\Filament\Resources\ViagemComplementos;

use App\Filament\Resources\ViagemComplementos\Pages\CreateViagemComplemento;
use App\Filament\Resources\ViagemComplementos\Pages\EditViagemComplemento;
use App\Filament\Resources\ViagemComplementos\Pages\ListViagemComplementos;
use App\Filament\Resources\ViagemComplementos\Schemas\ViagemComplementoForm;
use App\Filament\Resources\ViagemComplementos\Tables\ViagemComplementosTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\ViagemComplemento;
use UnitEnum;

class ViagemComplementoResource extends Resource
{
    protected static ?string $model = ViagemComplemento::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Viagens';

    protected static ?string $modelLabel = 'Complemento Viagem';

    protected static ?string $pluralModelLabel = 'Complementos Viagem';

    protected static ?string $recordTitleAttribute = 'numero_viagem';

    public static function form(Schema $schema): Schema
    {
        return ViagemComplementoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ViagemComplementosTable::configure($table);
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
            'index' => ListViagemComplementos::route('/'),
            'edit' => EditViagemComplemento::route('/{record}/edit'),
        ];
    }
}
