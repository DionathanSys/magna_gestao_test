<?php

namespace App\Filament\Resources\GarantiaServicos;

use App\Filament\Resources\GarantiaServicos\Pages\ListGarantiaServicos;
use App\Filament\Resources\GarantiaServicos\Tables\GarantiaServicosTable;
use App\Models\GarantiaServico;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class GarantiaServicoResource extends Resource
{
    protected static ?string $model = GarantiaServico::class;

    protected static string|UnitEnum|null $navigationGroup = 'Manutenção';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $modelLabel = 'Garantia de Serviço';

    protected static ?string $pluralModelLabel = 'Garantias de Serviços';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return GarantiaServicosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGarantiaServicos::route('/'),
        ];
    }
}
