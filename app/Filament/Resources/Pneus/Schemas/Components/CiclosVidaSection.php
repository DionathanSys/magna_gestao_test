<?php

namespace App\Filament\Resources\Pneus\Schemas\Components;

use App\Enum\Pneu\StatusCicloPneuEnum;
use App\Filament\Resources\DesenhoPneus\DesenhoPneuResource;
use App\Models\Pneu;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CiclosVidaSection
{
    public static function infolist(): Section
    {
        return Section::make('Histórico de Ciclos de Vida')
            ->description('Resumo dos ciclos da carcaça, com desenho, datas, quilometragem e vínculos operacionais.')
            ->columns(12)
            ->columnSpanFull()
            ->components([
                TextEntry::make('ciclos_historico')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->html()
                    ->state(fn (Pneu $record): string => self::renderCards($record)),
            ]);
    }

    public static function form(): Section
    {
        return Section::make('Ciclos de Vida')
            ->description('Edite os dados cadastrais dos ciclos. Recapagens, consertos e inspeções continuam nos relacionamentos próprios.')
            ->columns(12)
            ->columnSpanFull()
            ->schema([
                Repeater::make('ciclos')
                    ->hiddenLabel()
                    ->relationship('ciclos', fn ($query) => $query->orderByDesc('numero'))
                    ->columns(12)
                    ->columnSpanFull()
                    ->defaultItems(0)
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->collapsible()
                    ->itemLabel(fn (array $state): string => 'Ciclo '.($state['numero'] ?? '-'))
                    ->schema([
                        TextInput::make('numero')
                            ->label('Ciclo')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->columnSpan(2),
                        Select::make('status')
                            ->native(false)
                            ->options(StatusCicloPneuEnum::toSelectArray())
                            ->required()
                            ->columnSpan(3),
                        Select::make('desenho_pneu_id')
                            ->label('Desenho Borracha')
                            ->relationship('desenhoPneu', 'descricao', fn ($query) => $query->where('ativo', true))
                            ->createOptionForm(fn (Schema $schema) => DesenhoPneuResource::form($schema))
                            ->preload()
                            ->searchable()
                            ->columnSpan(7),
                        DatePicker::make('data_abertura')
                            ->label('Dt. Abertura')
                            ->date('d/m/Y')
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection()
                            ->columnSpan(3),
                        DatePicker::make('data_fechamento')
                            ->label('Dt. Fechamento')
                            ->date('d/m/Y')
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection()
                            ->columnSpan(3),
                        TextInput::make('km_inicial')
                            ->label('KM Inicial')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(3),
                        TextInput::make('km_final')
                            ->label('KM Final')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(3),
                        Textarea::make('observacao')
                            ->label('Observação')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function renderCards(Pneu $record): string
    {
        $ciclos = $record->ciclos()
            ->with('desenhoPneu')
            ->withCount(['recapagens', 'consertos', 'inspecoes'])
            ->orderByDesc('numero')
            ->get();

        if ($ciclos->isEmpty()) {
            return '<div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">Nenhum ciclo de vida registrado.</div>';
        }

        $html = '<div class="grid gap-4 lg:grid-cols-2">';

        foreach ($ciclos as $ciclo) {
            $status = $ciclo->status?->value ?? 'Não informado';
            $isAtual = $status === StatusCicloPneuEnum::ABERTO->value;
            $kmInicial = $ciclo->km_inicial !== null ? number_format((float) $ciclo->km_inicial, 0, ',', '.') : 'Não informado';
            $kmFinal = $ciclo->km_final !== null ? number_format((float) $ciclo->km_final, 0, ',', '.') : 'Em aberto';

            $html .= '<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm ring-1 ring-gray-950/5 dark:border-gray-700 dark:bg-gray-900">';
            $html .= '<div class="flex items-start justify-between gap-3 border-b border-gray-100 bg-gray-50 px-4 py-3 dark:border-gray-800 dark:bg-gray-950">';
            $html .= '<div><div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Ciclo de vida</div><div class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Ciclo '.e((string) $ciclo->numero).($isAtual ? ' atual' : '').'</div></div>';
            $html .= '<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold '.($isAtual ? 'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300').'">'.e($status).'</span>';
            $html .= '</div>';
            $html .= '<div class="grid gap-3 p-4 sm:grid-cols-2">';
            $html .= self::renderMetric('Desenho', $ciclo->desenhoPneu?->descricao ?? 'Sem desenho');
            $html .= self::renderMetric('Abertura', $ciclo->data_abertura?->format('d/m/Y') ?? 'Não informado');
            $html .= self::renderMetric('Fechamento', $ciclo->data_fechamento?->format('d/m/Y') ?? 'Em aberto');
            $html .= self::renderMetric('KM Inicial', $kmInicial);
            $html .= self::renderMetric('KM Final', $kmFinal);
            $html .= self::renderMetric('Recapagens', (string) $ciclo->recapagens_count);
            $html .= self::renderMetric('Consertos', (string) $ciclo->consertos_count);
            $html .= self::renderMetric('Inspeções', (string) $ciclo->inspecoes_count);
            $html .= '</div>';

            if (filled($ciclo->observacao)) {
                $html .= '<div class="border-t border-gray-100 px-4 py-3 text-sm text-gray-600 dark:border-gray-800 dark:text-gray-300"><span class="font-medium text-gray-500 dark:text-gray-400">Observação:</span> '.e($ciclo->observacao).'</div>';
            }

            $html .= '</div>';
        }

        return $html.'</div>';
    }

    private static function renderMetric(string $label, string $value): string
    {
        return '<div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-950"><div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">'.e($label).'</div><div class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">'.e($value).'</div></div>';
    }
}
