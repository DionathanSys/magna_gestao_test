<?php

namespace App\Filament\Resources\Abastecimentos;

use App\Filament\Resources\Abastecimentos\Pages\CreateAbastecimento;
use App\Filament\Resources\Abastecimentos\Pages\EditAbastecimento;
use App\Filament\Resources\Abastecimentos\Pages\ListAbastecimentos;
use App\Filament\Resources\Abastecimentos\Schemas\AbastecimentoForm;
use App\Filament\Resources\Abastecimentos\Tables\AbastecimentosTable;
use App\Filament\Resources\Abastecimentos\Widgets\ConsumoMedioDiesel;
use App\Models\Abastecimento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AbastecimentoResource extends Resource
{
    protected static ?string $model = Abastecimento::class;

    protected static string|UnitEnum|null $navigationGroup = 'Veículos';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-inbox';

    protected static ?string $modelLabel = 'Abastecimento';

    protected static ?string $pluralModelLabel = 'Abastecimentos';

    protected static ?string $recordTitleAttribute = 'id_abastecimento';

    public static function form(Schema $schema): Schema
    {
        return AbastecimentoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AbastecimentosTable::configure($table);
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
            'index' => ListAbastecimentos::route('/'),
            // 'create' => CreateAbastecimento::route('/create'),
            // 'edit' => EditAbastecimento::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            ConsumoMedioDiesel::class,
        ];
    }
}
