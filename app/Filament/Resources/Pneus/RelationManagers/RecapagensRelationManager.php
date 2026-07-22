<?php

namespace App\Filament\Resources\Pneus\RelationManagers;

use App\Filament\Resources\DesenhoPneus\DesenhoPneuResource;
use App\Models\Pneu;
use App\Models\Recapagem;
use App\Services\NotificacaoService as notify;
use App\Services\Pneus\PneuService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class RecapagensRelationManager extends RelationManager
{
    protected static string $relationship = 'recapagens';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('data_recapagem')
                    ->date('d/m/Y')
                    ->displayFormat('d/m/Y')
                    ->closeOnDateSelection()
                    ->maxDate(now())
                    ->required(),
                Select::make('desenho_pneu_id')
                    ->label('Desenho do Pneu')
                    ->relationship('desenhoPneu', 'descricao', fn ($query) => $query->where('ativo', true))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->createOptionForm(fn (Schema $schema) => DesenhoPneuResource::form($schema)),
                TextInput::make('valor')
                    ->label('Valor')
                    ->numeric()
                    ->default(0)
                    ->prefix('R$'),
                TextInput::make('ciclo_vida')
                    ->label('Ciclo de Vida')
                    ->numeric()
                    ->default(fn () => ((int) $this->getOwnerRecord()->ciclo_vida) + 1)
                    ->minValue(1)
                    ->maxValue(9),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('pneu_id')
            ->columns([
                TextColumn::make('pneu_id')
                    ->searchable()
                    ->width('1%'),
                TextColumn::make('data_recapagem')
                    ->date('d/m/Y')
                    ->width('1%'),
                TextColumn::make('pneu.modeloCatalogo.nome')
                    ->label('Modelo Carcaça')
                    ->width('1%'),
                TextColumn::make('desenhoPneu.descricao')
                    ->label('Desenho')
                    ->width('1%'),
                TextColumn::make('desenhoPneu.modelo')
                    ->label('Modelo Desenho')
                    ->width('1%'),
                TextColumn::make('ciclo_vida')
                    ->label('Ciclo de Vida')
                    ->width('1%'),
                TextColumn::make('valor')
                    ->money('BRL')
                    ->searchable()
                    ->width('1%'),
                TextColumn::make('created_at')
                    ->date('d/m/Y H:i'),
            ])
            ->groups([
                Group::make('pneu.numero_fogo')
                    ->label('Nº Fogo'),
            ])
            ->filters([
                SelectFilter::make('pneu_id')
                    ->label('Pneu')
                    ->relationship('pneu', 'numero_fogo')
                    ->multiple()
                    ->searchable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data) {
                        /** @var Pneu $pneu */
                        $pneu = $this->getOwnerRecord();

                        $service = new PneuService;
                        $recapagem = $service->recapar([
                            ...$data,
                            'pneu_id' => $pneu->id,
                            'ignorar_validacao_inspecao' => true,
                        ]);

                        if (! $recapagem) {
                            throw ValidationException::withMessages([
                                'data_recapagem' => $service->getMessage(),
                            ]);
                        }

                        return $recapagem;
                    }),
            ])
            ->recordActions([
                Action::make('reverter-recapagem')
                    ->label('Reverter')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->modalHeading('Reverter recapagem')
                    ->modalDescription('Remove esta recapagem, volta a vida do pneu em 1 e reabre o ciclo anterior.')
                    ->visible(fn (Recapagem $record): bool => $this->isRecapagemAtual($record))
                    ->action(function (Action $action): void {
                        $service = new PneuService;
                        $service->reverterRecapagem($this->getOwnerRecord());

                        if ($service->hasError()) {
                            notify::error(titulo: 'Falha ao reverter recapagem', mensagem: $service->getMessage());
                            $action->halt();
                        }

                        notify::success('Recapagem revertida com sucesso.');
                    }),
                EditAction::make()
                    ->iconButton(),
                DeleteAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private function isRecapagemAtual(Recapagem $recapagem): bool
    {
        /** @var Pneu $pneu */
        $pneu = $this->getOwnerRecord();

        $ultimaRecapagemId = $pneu->recapagens()
            ->orderByDesc('ciclo_vida')
            ->orderByDesc('data_recapagem')
            ->orderByDesc('id')
            ->value('id');

        return (int) $recapagem->id === (int) $ultimaRecapagemId
            && (int) $recapagem->ciclo_vida === (int) $pneu->ciclo_vida
            && (int) $pneu->ciclo_vida > 0;
    }
}
