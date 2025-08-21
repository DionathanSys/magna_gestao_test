<?php

namespace App\Filament\Resources\OrdemServicos;

use App\Filament\Resources\OrdemServicos\Pages\CreateOrdemServico;
use App\Filament\Resources\OrdemServicos\Pages\EditOrdemServico;
use App\Filament\Resources\OrdemServicos\Pages\ListOrdemServicos;
use App\Filament\Resources\OrdemServicos\Pages\OrdemServico as PagesOrdemServico;
use App\Filament\Resources\OrdemServicos\Schemas\OrdemServicoForm;
use App\Filament\Resources\OrdemServicos\Tables\OrdemServicosTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\OrdemServico;

class OrdemServicoResource extends Resource
{
    protected static ?string $model = OrdemServico::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return OrdemServicoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdemServicosTable::configure($table);
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
            'index' => ListOrdemServicos::route('/'),
            'teste' => PagesOrdemServico::route('/{record}/teste'),
            'edit' => EditOrdemServico::route('/{record}/edit'),
        ];
    }
}
