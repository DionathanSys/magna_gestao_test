<?php

namespace App\Filament\Resources\OrdemServicos\Schemas;

use App\Enum;
use App\Filament\Resources\Servicos\Schemas\ServicoForm;
use App\Services\Servico\ServicoCacheService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ItemOrdemServicoForm
{
    public static function configure(Schema $schema, bool $includeStatus = false): Schema
    {
        return $schema
            ->columns([
                'sm' => 1,
                'md' => 4,
                'lg' => 8,
            ])
            ->components([
                self::getServicoIdFormField()
                    ->columnStart(1)
                    ->columnSpan([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ]),
                self::getControlaPosicaoFormField()
                    ->columnSpan([
                        'sm' => 1,
                        'md' => 1,
                        'lg' => 2,
                    ]),
                self::getPosicaoFormField()
                    ->columnSpan([
                        'sm' => 1,
                        'md' => 1,
                        'lg' => 2,
                    ]),
                self::getStatusFormField()
                    ->columnSpan([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 3,
                    ])
                    ->visible(fn (): bool => $includeStatus),
                self::getObservacaoFormField()
                    ->columnSpanFull(),
            ]);
    }

    public static function getServicoIdFormField(): Select
    {
        return Select::make('servico_id')
            ->label('Serviço')
            ->required()
            // ->relationship('servico', 'descricao') //TODO Não está funcionando por usar no OrdemTable talvez criar um relationship 'servico' de certo
            ->options(fn (): array => ServicoCacheService::getServicosForSelect())
            ->getSearchResultsUsing(fn (string $search): array => ServicoCacheService::searchServicosForSelect($search))
            ->getOptionLabelUsing(fn ($value): ?string => ServicoCacheService::getServicoLabel($value))
            // ->createOptionForm(fn(Schema $schema) => ServicoForm::configure($schema))    //TODO Não está funcionando por não usar o relationship
            // ->editOptionForm(fn(Schema $schema) => ServicoForm::configure($schema))
            ->searchable()
            ->live()
            ->preload()
            ->afterStateUpdated(function (Set $set, $state): void {
                $set('controla_posicao', ServicoCacheService::controlaPosicao($state));

                $set('posicao', null);
            });
    }

    public static function getControlaPosicaoFormField(): Toggle
    {
        return Toggle::make('controla_posicao')
            ->label('Controla Posição')
            ->inline(false)
            ->disabled()
            ->dehydrated(false)
            ->live();
    }

    public static function getPosicaoFormField(): Select
    {
        return Select::make('posicao')
            ->label('Posição')
            ->options(function (Get $get): array {
                $servicoId = $get('servico_id');

                return ServicoCacheService::getPosicoesForSelect($servicoId);
            })
            ->placeholder('Selecione a posição')
            ->searchable()
            ->preload()
            ->visible(fn (Get $get): bool => (bool) $get('controla_posicao'))
            ->requiredIf('controla_posicao', true)
            ->dehydrated(fn (Get $get): bool => (bool) $get('controla_posicao'));
    }

    public static function getObservacaoFormField(): Textarea
    {
        return Textarea::make('observacao')
            ->label('Observação')
            ->maxLength(200);
    }

    public static function getStatusFormField(): Select
    {
        return Select::make('status')
            ->label('Status')
            ->options(Enum\OrdemServico\StatusOrdemServicoEnum::toSelectArray())
            ->default(Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE->value)
            ->required();
    }
}
