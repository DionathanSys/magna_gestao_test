<?php

namespace App\Filament\Resources\ManutencaoCustos;

use App\Filament\Resources\ManutencaoCustos\Pages\CreateManutencaoCusto;
use App\Filament\Resources\ManutencaoCustos\Pages\EditManutencaoCusto;
use App\Filament\Resources\ManutencaoCustos\Pages\ListManutencaoCustos;
use App\Filament\Resources\ManutencaoCustos\Schemas\ManutencaoCustoForm;
use App\Filament\Resources\ManutencaoCustos\Tables\ManutencaoCustosTable;
use App\Models\ManutencaoCusto;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ManutencaoCustoResource extends Resource
{
    protected static ?string $model = ManutencaoCusto::class;

    protected static string|UnitEnum|null $navigationGroup = 'Manutenção';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::Banknotes;
    
    protected static ?string $modelLabel = 'Custos de Manutenção';

    protected static ?string $pluralModelLabel = 'Custos de Manutenção';

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return ManutencaoCustoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ManutencaoCustosTable::configure($table);
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
            'index' => ListManutencaoCustos::route('/'),
            // 'create' => CreateManutencaoCusto::route('/create'),
            // 'edit' => EditManutencaoCusto::route('/{record}/edit'),
        ];
    }
}
