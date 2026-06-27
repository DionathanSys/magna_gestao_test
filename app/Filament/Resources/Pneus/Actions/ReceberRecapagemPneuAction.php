<?php

namespace App\Filament\Resources\Pneus\Actions;

use App\Filament\Resources\DesenhoPneus\DesenhoPneuResource;
use App\Models\Pneu;
use App\Services\NotificacaoService as notify;
use App\Services\Pneus\PneuService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class ReceberRecapagemPneuAction
{
    public static function make(): Action
    {
        return Action::make('receber-recapagem')
            ->label('Receber do Recap')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->modalWidth(Width::ExtraLarge)
            ->visible(fn (Pneu $record) => $record->status->value === 'INDISPONIVEL' && $record->local?->value === 'AGUARDANDO RETORNO RECAP')
            ->fillForm(fn () => ['resultado_retorno' => 'APROVADO', 'data_recapagem' => now()->toDateString()])
            ->schema(fn (Schema $schema) => $schema
                ->columns(12)
                ->schema([
                    Select::make('resultado_retorno')
                        ->label('Resultado do retorno')
                        ->options([
                            'APROVADO' => 'Aprovado',
                            'RETORNAR_ESTOQUE' => 'Retornar ao estoque sem recapar',
                            'RECUSADO' => 'Recusado / Descarte automático',
                        ])
                        ->native(false)
                        ->required()
                        ->live()
                        ->columnSpan(6),
                    DatePicker::make('data_recapagem')
                        ->label('Dt. Retorno / Recapagem')
                        ->required()
                        ->default(now())
                        ->columnSpan(6),
                    TextInput::make('valor')
                        ->label('Valor')
                        ->numeric()
                        ->default(0)
                        ->prefix('R$')
                        ->visible(fn (Get $get) => $get('resultado_retorno') === 'APROVADO')
                        ->columnSpan(4),
                    Select::make('desenho_pneu_id')
                        ->label('Desenho Borracha')
                        ->relationship('desenhoPneu', 'descricao', fn ($query) => $query->where('ativo', true))
                        ->searchable()
                        ->preload()
                        ->createOptionForm(fn (Schema $schema) => DesenhoPneuResource::form($schema))
                        ->required(fn (Get $get) => $get('resultado_retorno') === 'APROVADO')
                        ->visible(fn (Get $get) => $get('resultado_retorno') === 'APROVADO')
                        ->columnSpan(8),
                ]))
            ->action(function (Action $action, Pneu $record, array $data): void {
                $service = new PneuService;
                $service->receberRetornoRecapagem($record, $data);

                if ($service->hasError()) {
                    notify::error(titulo: 'Falha ao receber recapagem', mensagem: $service->getMessage());
                    $action->halt();
                }

                notify::success('Retorno de recapagem processado com sucesso.');
            });
    }
}
