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
                        TextInput::make('numero_viagem')
                            ->required()
                            ->columnSpan(6),
                        TextInput::make('documento_transporte')
                            ->columnSpan(6),
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
                Section::make('Comentarios')
                    ->columnStart(1)
                    ->columnSpan(12)
                    ->schema([
                        EmptyState::make('No users yet')
                            ->description('Get started by creating a new user.')
                            ->icon(Heroicon::ChatBubbleBottomCenterText)
                            ->visible(fn($record) => (
                                // se não houver registro (novo), considera sem comentários
                                $record === null
                                || (method_exists($record, 'comentarios') && $record->comentarios()->count() === 0)
                            )),
                        RepeatableEntry::make('comentarios')
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
