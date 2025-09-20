<?php

namespace App\Filament\Resources\Veiculos;

use App\Filament\Resources\Veiculos\Pages\CreateVeiculo;
use App\Filament\Resources\Veiculos\Pages\EditVeiculo;
use App\Filament\Resources\Veiculos\Pages\ListVeiculos;
use App\Filament\Resources\Veiculos\RelationManagers\ManutencoesRelationManager;
use App\Filament\Resources\Veiculos\RelationManagers\PneusRelationManager;
use App\Filament\Resources\Veiculos\Schemas\VeiculoForm;
use App\Filament\Resources\Veiculos\Tables\VeiculosTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Veiculo;
use UnitEnum;

class VeiculoResource extends Resource
{
    protected static ?string $model = Veiculo::class;

    protected static string|UnitEnum|null $navigationGroup = 'Cadastro';

    protected static ?string $recordTitleAttribute = 'placa';

    public static function form(Schema $schema): Schema
    {
        return VeiculoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VeiculosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PneusRelationManager::class,
            ManutencoesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVeiculos::route('/'),
            'create' => CreateVeiculo::route('/create'),
            'edit' => EditVeiculo::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
