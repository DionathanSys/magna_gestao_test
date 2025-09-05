<?php

namespace App\Filament\Resources\OrdemServicos\Actions;

use Filament\Actions\CreateAction;
use App\Models;
use App\Services;
use App\Enum;
use App\Filament\Resources\Servicos\Schemas\ServicoForm;
use App\Services\NotificacaoService as notify;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class VincularServicoOrdemServicoAction
{
    public static function make(?int $ordemServicoId = null): CreateAction
    {
        return CreateAction::make()
            ->label('Adicionar Serviço')
            ->icon('heroicon-o-plus')
            ->schema(
                fn(Schema $schema) => $schema
                    ->columns([
                        'sm' => 1,
                        'md' => 4,
                        'lg' => 8,
                    ])
                    ->components(
                        [
                            self::getServicoIdFormField()
                                ->columnStart(1)
                                ->columnSpan([
                                    'sm' => 1,
                                    'md' => 2,
                                    'lg' => 3
                                ]),
                            self::getControlaPosicaoFormField()
                                ->columnSpan([
                                    'sm' => 1,
                                    'md' => 1,
                                    'lg' => 2
                                ]),
                            self::getPosicaoFormField()
                                ->columnSpan([
                                    'sm' => 1,
                                    'md' => 1,
                                    'lg' => 2
                                ]),
                            self::getStatusFormField()
                                ->columnSpan([
                                    'sm' => 1,
                                    'md' => 2,
                                    'lg' => 3
                                ]),
                            self::getObservacaoFormField()
                                ->columnSpanFull(),
                        ]
                    )
            )
            ->model(Models\ItemOrdemServico::class) // Definir o model
            ->mutateDataUsing(function (array $data) use ($ordemServicoId): array {
                $data['ordem_servico_id'] = $ordemServicoId;
                return $data;
            })
            ->using(function (array $data, string $model, CreateAction $action): ?Model {
                $service = new Services\ItemOrdemServico\ItemOrdemServicoService();
                $itemOrdemServico = $service->create($data);

                if ($service->hasError()) {
                    notify::error(mensagem: $service->getMessage());
                    $action->halt();
                    return null;
                }
                notify::success(mensagem: 'Serviço vinculado com sucesso!');
                return $itemOrdemServico;
            });
    }

    public static function getServicoIdFormField(): Select
    {
        return Select::make('servico_id')
            ->label('Serviço')
            ->required()
            ->relationship('servico', 'descricao')
            ->createOptionForm(fn(Schema $schema) => ServicoForm::configure($schema))
            ->editOptionForm(fn(Schema $schema) => ServicoForm::configure($schema))
            ->searchable()
            ->preload()
            ->live()
            ->afterStateUpdated(function (Set $set, $state) {
                if ($state) {
                    $servico = \App\Models\Servico::find($state);
                    $set('controla_posicao', $servico?->controla_posicao ? true : false);
                } else {
                    $set('controla_posicao', false);
                }
            });
    }

    public static function getControlaPosicaoFormField(): Toggle
    {
        return Toggle::make('controla_posicao')
            ->label('Controla Posição')
            ->inline(false)
            ->disabled()
            ->live();
    }

    public static function getPosicaoFormField(): TextInput
    {
        return TextInput::make('posicao')
            ->label('Posição')
            ->requiredIf('controla_posicao', true)
            ->minLength(2)
            ->maxLength(7);
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
