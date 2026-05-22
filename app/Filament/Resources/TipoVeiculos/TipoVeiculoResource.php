<?php

namespace App\Filament\Resources\TipoVeiculos;

use App\Enum\Pneu\ConfiguracaoMapaPneusEnum;
use App\Filament\Resources\TipoVeiculos\Pages\ManageTipoVeiculos;
use App\Models\TipoVeiculo;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class TipoVeiculoResource extends Resource
{
    protected static ?string $model = TipoVeiculo::class;

    protected static string|UnitEnum|null $navigationGroup = 'Cadastro';

    protected static ?string $recordTitleAttribute = 'descricao';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descricao')
                    ->label('Descrição')
                    ->required(),
                Select::make('configuracao_pneus')
                    ->label('Mapa de Pneus')
                    ->options(ConfiguracaoMapaPneusEnum::toSelectArray())
                    ->native(false)
                    ->helperText('Define o layout visual usado no mapa de pneus.')
                    ->required(),
                TextInput::make('meta_media')
                    ->label('Meta Média')
                    ->required()
                    ->numeric()
                    ->minValue(0.01),
                Toggle::make('is_active')
                    ->label('Ativo')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descricao')
            ->columns([
                TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable(isIndividual: true, isGlobal: false),
                TextColumn::make('configuracao_pneus')
                    ->label('Mapa de Pneus')
                    ->formatStateUsing(function ($state) {
                        if ($state instanceof ConfiguracaoMapaPneusEnum) {
                            return $state->label();
                        }

                        return is_string($state)
                            ? (ConfiguracaoMapaPneusEnum::tryFrom($state)?->label() ?? '-')
                            : '-';
                    }),
                TextColumn::make('meta_media')
                    ->label('Meta Média')
                    ->numeric(2, ','),
                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Criado Em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado Em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (): bool => Auth::user()->is_admin),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => Auth::user()->is_admin),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTipoVeiculos::route('/'),
        ];
    }
}
