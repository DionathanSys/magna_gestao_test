<?php

namespace App\Filament\Resources\OrdemServicos\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use App\Enum;
use App\Models;
use App\Filament\Resources\Servicos\Schemas\ServicoForm;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;

class ItemOrdemServicoForm
{
    public static function configure(Schema $schema): Schema
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
            ]);
    }

    public static function getServicoIdFormField(): Select
    {
        return Select::make('servico_id')
            ->label('Serviço')
            ->required()
            // ->relationship('servico', 'descricao')
            ->getSearchResultsUsing(fn(string $search): array => Models\Servico::query()
                ->where('descricao', 'like', "%{$search}%")
                ->limit(10)
                ->pluck('descricao', 'id')
                ->all())
            ->getOptionLabelUsing(fn($value): ?string => Models\Servico::find($value)?->descricao)
            ->createOptionForm(fn(Schema $schema) => ServicoForm::configure($schema))
            ->editOptionForm(fn(Schema $schema) => ServicoForm::configure($schema))
            ->searchable()
            ->preload()
            ->live()
            ->afterStateUpdated(function (Set $set, $state) {
                if ($state) {
                    $servico = Models\Servico::find($state);
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
