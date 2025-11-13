<?php

namespace App\Filament\Resources\Viagems\Schemas;

use Filament\Forms\Components\{
    DatePicker,
    DateTimePicker,
    Repeater,
    Select,
    TextInput,
    Toggle
};
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Enum;
use App\Filament\Resources\Viagems\Actions\AdicionarComentarioAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\EmptyState;
use Filament\Support\Icons\Heroicon;

class ViagemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make('Dados Viagem')
                    ->columns(12)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('veiculo_id')
                            ->label('Veículo')
                            ->relationship('veiculo', 'placa', function ($query) {
                                $query->where('is_active', true);
                                $query->orderBy('placa');
                            })
                            ->searchPrompt('Buscar Veículo')
                            ->placeholder('Buscar ...')
                            ->required()
                            ->searchable()
                            ->columnSpan(4),
                        Select::make('unidade_negocio')
                            ->label('Unidade de Negócio')
                            ->options([
                                'CHAPECO'       => 'Chapecó',
                                'CATANDUVAS'    => 'Catanduvas',
                                'CONCORDIA'     => 'Concórdia',
                            ])
                            ->default('CHAPECO')
                            ->required()
                            ->columnSpan(4),
                        TextInput::make('numero_viagem')
                            ->required()
                            ->columnStart(1)
                            ->columnSpan(3),
                        TextInput::make('documento_transporte')
                            ->columnSpan(3),
                        Toggle::make('considerar_relatorio')
                            ->columnSpan(3)
                            ->label('Considerar no Relatório')
                            ->inline(false)
                            ->default(false),
                        DatePicker::make('data_competencia')
                            ->columnStart(1)
                            ->columnSpan(4)
                            ->required(),
                        DateTimePicker::make('data_inicio')
                            ->columnSpan(4)
                            ->required(),
                        DateTimePicker::make('data_fim')
                            ->columnSpan(4)
                            ->required(),
                    ]),
                Section::make('Quilometragens')
                    ->columns(12)
                    ->columnStart(1)
                    ->columnSpan(12)
                    ->schema([
                        TextInput::make('km_rodado')
                            ->columnSpan(2)
                            ->numeric()
                            ->default(0),
                        TextInput::make('km_pago')
                            ->columnSpan(2)
                            ->numeric()
                            ->default(0),
                        TextInput::make('km_cobrar')
                            ->columnSpan(2)
                            ->numeric()
                            ->default(0),
                        TextInput::make('km_cadastro')
                            ->columnSpan(2)
                            ->numeric()
                            ->default(0),
                        Select::make('motivo_divergencia')
                            ->label('Motivo Divergência')
                            ->columnSpan(5)
                            ->native(false)
                            ->options(Enum\MotivoDivergenciaViagem::toSelectArray())
                            ->default(Enum\MotivoDivergenciaViagem::DESLOCAMENTO_OUTROS->value),

                    ]),
                Section::make('Documentos')
                    ->columnStart(1)
                    ->columnSpan(12)
                    ->schema([
                        RepeatableEntry::make('documentos')
                            ->placeholder('Nenhum documento vinculado')
                            ->columnSpan(12)
                            ->table([
                                TableColumn::make('Destino'),
                                TableColumn::make('Nº Doc.'),
                                TableColumn::make('Tipo Doc.'),
                                TableColumn::make('Valor Total'),
                                TableColumn::make('Valor ICMS'),
                            ])
                            ->schema([
                                TextEntry::make('parceiro_destino'),
                                TextEntry::make('numero_documento'),
                                TextEntry::make('tipo_documento'),
                                TextEntry::make('valor_total')
                                    ->money('BRL'),
                                TextEntry::make('valor_icms')
                                    ->money('BRL'),
                            ])
                    ]),
                Section::make('Integrados')
                    ->columnStart(1)
                    ->columnSpan(12)
                    ->schema([
                        RepeatableEntry::make('integrados')
                            ->placeholder('Nenhum integrado adicionado')
                            ->columnSpan(12)
                            ->table([
                                TableColumn::make('Id'),
                                TableColumn::make('Nome'),
                                TableColumn::make('Km Rota'),
                                TableColumn::make('Cidade'),
                                TableColumn::make('Estado'),


                            ])
                            ->schema([
                                TextEntry::make('id'),
                                TextEntry::make('nome'),
                                TextEntry::make('km_rota'),
                                TextEntry::make('cidade'),
                                TextEntry::make('estado'),

                            ])
                    ]),
                Section::make('Comentarios')
                    ->columnStart(1)
                    ->columnSpan(12)
                    ->headerActions([
                        AdicionarComentarioAction::make()
                    ])
                    ->schema([
                        RepeatableEntry::make('comentarios')
                            ->placeholder('Nenhum comentário adicionado')
                            ->table([
                                TableColumn::make('Conteúdo')
                                    ->wrapHeader(),
                                TableColumn::make('Criado Em'),
                                TableColumn::make('Criado Por'),
                            ])
                            ->schema([
                                TextEntry::make('conteudo')
                                    ->label('Comentário')
                                    ->html(),
                                TextEntry::make('created_at')
                                    ->label('Criado em')
                                    ->dateTime('d/m/Y H:i'),
                                TextEntry::make('creator.name')
                                    ->label('Criado por'),
                            ])
                    ])


            ]);
    }
}
