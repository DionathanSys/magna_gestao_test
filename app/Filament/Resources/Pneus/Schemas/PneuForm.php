<?php

namespace App\Filament\Resources\Pneus\Schemas;

use App\Filament\Resources\Pneus\Actions;
use App\Enum;
use App\Filament\Resources\DesenhoPneus\DesenhoPneuResource;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;

class PneuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Dados do Pneu')
                    ->columns(12)
                    ->columnSpanFull()
                    ->description('Preencha os dados para cadastro do pneu.')
                    ->schema([
                        Components\NumeroFogoInput::make()
                            ->columnSpan(6),
                        Components\CicloVidaInput::make()
                            ->columnSpan(2),
                        TextInput::make('valor')
                            ->columnSpan(4)
                            ->columnStart(1)
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->prefix('R$'),
                        DatePicker::make('data_aquisicao')
                            ->label('Dt. Aquisição')
                            ->columnSpan(4)
                            ->default(now())
                            ->maxDate(now())
                            ->required(),
                        Select::make('medida')
                            ->columnSpan(4)
                            ->columnStart(1)
                            ->options([
                                '275/80 R22.5' => '275/80 R22.5',
                                '295/80 R22.5' => '295/80 R22.5',
                            ])
                            ->default('275/80 R22.5'),
                        Components\MarcaInput::make()
                            ->columnSpan(4),
                        Components\ModeloInput::make()
                            ->columnStart(1)
                            ->columnSpan(4),
                        Components\DesenhoPneuInput::make()
                            ->columnSpan(4),
                        Select::make('status')
                            ->columnStart(1)
                            ->columnSpan(4)
                            ->options(Enum\Pneu\StatusPneuEnum::toSelectArray())
                            ->required()
                            ->default(Enum\Pneu\StatusPneuEnum::DISPONIVEL->value),
                        Select::make('local')
                            ->columnSpan(4)
                            ->options(Enum\Pneu\LocalPneuEnum::toSelectArray())
                            ->required()
                            ->default(Enum\Pneu\LocalPneuEnum::ESTOQUE_CCO->value),
                        
                    ]),
                Section::make('Recapagem')
                    ->description('Registrar recapagem do pneu.')
                    ->visibleOn('create')
                    ->columns(12)
                    ->columnSpanFull()
                    ->afterHeader([
                        Actions\RecaparPneuAction::make('recapar')
                            ->mutateDataUsing(fn (array $data) => self::mutateDataRecap($data))
                            ->tooltip('Apenas para uso de pneus já cadastrados')
                            ->disabled(fn (Get $get) => empty($get('recap.pneu_id'))),
                    ])
                    ->schema([
                        Hidden::make('recap.pneu_id'),
                        DatePicker::make('recap.data_recapagem')    
                            ->date('d/m/Y')
                            ->columnSpan(3)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection()
                            ->default(now())
                            ->maxDate(now()),
                        TextInput::make('recap.valor_recapagem')
                            ->label('Valor')
                            ->columnSpan(3)
                            ->numeric()
                            ->default(0)
                            ->prefix('R$'),
                        Select::make('recap.desenho_pneu_id_recapagem')
                            ->label('Desenho Borracha')
                            ->relationship('desenhoPneu', 'descricao', fn($query) => $query->where('estado_pneu', Enum\Pneu\EstadoPneuEnum::RECAPADO))
                            ->searchable()
                            ->preload()
                            ->createOptionForm(fn(Schema $schema) => DesenhoPneuResource::form($schema))
                            ->columnSpan(4),

                    ])

            ]);
    }

    private static function mutateDataRecap(array $data): array
    {
        //Normalizar os indices do array, devido conflito de nomes no form
        //entre os campos do pneu e da recapagem
        return [
            'pneu_id'           => $data['pneu_id'],
            'valor'             => $data['valor_recapagem'],
            'desenho_pneu_id'   => $data['desenho_pneu_id_recapagem'],
            'data_recapagem'    => $data['data_recapagem'],
        ];
    }
}
