<?php

namespace App\Filament\Resources\AnotacaoVeiculos;

use App\Filament\Resources\AnotacaoVeiculos\Pages\CreateAnotacaoVeiculo;
use App\Filament\Resources\AnotacaoVeiculos\Pages\EditAnotacaoVeiculo;
use App\Filament\Resources\AnotacaoVeiculos\Pages\ListAnotacaoVeiculos;
use App\Filament\Resources\AnotacaoVeiculos\Pages\ViewAnotacaoVeiculo;
use App\Filament\Resources\AnotacaoVeiculos\Schemas\AnotacaoVeiculoForm;
use App\Filament\Resources\AnotacaoVeiculos\Schemas\AnotacaoVeiculoInfolist;
use App\Filament\Resources\AnotacaoVeiculos\Tables\AnotacaoVeiculosTable;
use App\Models\AnotacaoVeiculo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AnotacaoVeiculoResource extends Resource
{
    protected static ?string $model = AnotacaoVeiculo::class;

    protected static string|UnitEnum|null $navigationGroup = 'Manutenção';

    protected static ?string $modelLabel = 'Anotações Veiculo';

    protected static ?string $pluralModelLabel = 'Anotações Veículo';

    protected static ?string $recordTitleAttribute = 'veiculo_id';

    public static function form(Schema $schema): Schema
    {
        return AnotacaoVeiculoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AnotacaoVeiculoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnotacaoVeiculosTable::configure($table);
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
            'index' => ListAnotacaoVeiculos::route('/'),
            'create' => CreateAnotacaoVeiculo::route('/create'),
            'view' => ViewAnotacaoVeiculo::route('/{record}'),
            'edit' => EditAnotacaoVeiculo::route('/{record}/edit'),
        ];
    }
}
