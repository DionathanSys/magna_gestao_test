<?php

namespace App\Filament\Resources\AnaliseServicosOrdemServicos;

use App\Filament\Resources\AnaliseServicosOrdemServicos\Pages\ListAnaliseServicosOrdemServicos;
use App\Filament\Resources\AnaliseServicosOrdemServicos\Tables\AnaliseServicosOrdemServicosTable;
use App\Models\ItemOrdemServico;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class AnaliseServicosOrdemServicoResource extends Resource
{
    protected static ?string $model = ItemOrdemServico::class;

    protected static string|UnitEnum|null $navigationGroup = 'Manutenção';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Análise Serviços OS';

    protected static ?string $modelLabel = 'Serviço de OS';

    protected static ?string $pluralModelLabel = 'Análise de Serviços de OS';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return AnaliseServicosOrdemServicosTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnaliseServicosOrdemServicos::route('/'),
        ];
    }
}
