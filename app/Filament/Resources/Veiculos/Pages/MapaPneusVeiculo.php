<?php

namespace App\Filament\Resources\Veiculos\Pages;

use App\Enum\Pneu\TipoInspecaoPneuEnum;
use App\Filament\Resources\PneuInspecoes\Schemas\PneuInspecaoForm;
use App\Filament\Resources\Veiculos\VeiculoResource;
use App\Models\PneuInspecao;
use App\Models\PneuPosicaoVeiculo;
use App\Models\Veiculo;
use App\Support\Pneus\MapaPneusLayout;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class MapaPneusVeiculo extends Page implements HasActions
{
    use InteractsWithActions;

    protected static string $resource = VeiculoResource::class;

    protected static ?string $title = 'Mapa de Pneus';

    protected string $view = 'filament.resources.veiculos.pages.mapa-pneus-veiculo';

    public int|string $recordId;

    public ?int $selectedPosicaoId = null;

    public function mount(int|string $record): void
    {
        $this->recordId = $record;
        $this->selectedPosicaoId = request()->integer('selected') ?: $this->getPosicoes()->first()?->id;
    }

    public static function canAccess(array $parameters = []): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('voltar')
                ->label('Voltar ao veículo')
                ->url(fn () => VeiculoResource::getUrl('edit', ['record' => $this->getRecord()], false)),
        ];
    }

    public function selectPosicao(int $posicaoId): void
    {
        $this->selectedPosicaoId = $posicaoId;
    }

    public function openInspection(int $posicaoId): void
    {
        $this->selectedPosicaoId = $posicaoId;
        $this->replaceMountedAction('inspecionarPosicao', ['posicao' => $posicaoId]);
    }

    public function getRecord(): Veiculo
    {
        return Veiculo::query()
            ->with(['kmAtual', 'tipoVeiculo'])
            ->findOrFail($this->recordId);
    }

    public function getViewData(): array
    {
        $posicoes = $this->getPosicoes();

        return [
            'record' => $this->getRecord(),
            'mapa' => MapaPneusLayout::build($this->getRecord(), $posicoes, $this->selectedPosicaoId),
        ];
    }

    protected function getPosicoes()
    {
        return PneuPosicaoVeiculo::query()
            ->where('veiculo_id', $this->recordId)
            ->with([
                'pneu.inspecoes' => fn ($query) => $query->latest('data_inspecao')->latest('id'),
                'pneu.marcaCatalogo',
                'pneu.modeloCatalogo',
                'pneu.medidaCatalogo',
                'veiculo.kmAtual',
            ])
            ->orderBy('sequencia')
            ->get();
    }

    public function inspecionarPosicaoAction(): Action
    {
        return Action::make('inspecionarPosicao')
            ->label('Inspecionar')
            ->icon('heroicon-o-clipboard-document-check')
            ->visible(function (?PneuPosicaoVeiculo $record, array $arguments): bool {
                $posicao = $record ?? $this->resolvePosicaoFromArguments($arguments);

                return filled($posicao?->pneu_id);
            })
            ->slideOver()
            ->fillForm(function (?PneuPosicaoVeiculo $record, array $arguments): array {
                $posicao = $record ?? $this->resolvePosicaoFromArguments($arguments);

                return $this->getInspectionFormData($posicao);
            })
            ->schema(function (?PneuPosicaoVeiculo $record, array $arguments): array {
                $posicao = $record ?? $this->resolvePosicaoFromArguments($arguments);

                return array_merge([
                    Section::make('Resumo do Pneu')
                        ->columns(12)
                        ->collapsible()
                        ->persistCollapsed()
                        ->components([
                            Grid::make([
                                'default' => 1,
                                'md' => 2,
                                'xl' => 3,
                            ])
                                ->columnSpanFull()
                                ->schema([
                                    TextInput::make('pneu_info')->label('Pneu')->readOnly()->disabled()->dehydrated(false),
                                    TextInput::make('veiculo_info')->label('Veículo')->readOnly()->disabled()->dehydrated(false),
                                    TextInput::make('posicao_info')->label('Posição')->readOnly()->disabled()->dehydrated(false),
                                    TextInput::make('medida_info')->label('Medida')->readOnly()->disabled()->dehydrated(false),
                                    TextInput::make('ciclo_info')->label('Ciclo Atual')->readOnly()->disabled()->dehydrated(false),
                                    TextInput::make('km_info')->label('Km Rodado na Posição')->readOnly()->disabled()->dehydrated(false),
                                    TextInput::make('ultima_inspecao_info')->label('Última Inspeção')->readOnly()->disabled()->dehydrated(false),
                                    TextInput::make('ultimo_resultado_info')->label('Último Resultado')->readOnly()->disabled()->dehydrated(false),
                                    TextInput::make('ultimo_sulco_info')->label('Último Sulco')->readOnly()->disabled()->dehydrated(false),
                                ]),
                        ]),
                ], PneuInspecaoForm::getComponentsForOperationalAction());
            })
            ->action(function (array $data): void {
                PneuInspecao::create($data);

                Notification::make()
                    ->title('Inspeção registrada com sucesso')
                    ->success()
                    ->send();
            });
    }

    protected function resolvePosicaoFromArguments(array $arguments): ?PneuPosicaoVeiculo
    {
        $posicaoId = $arguments['posicao'] ?? null;

        if (! $posicaoId) {
            return null;
        }

        return $this->getPosicoes()->firstWhere('id', (int) $posicaoId);
    }

    protected function getInspectionFormData(?PneuPosicaoVeiculo $record): array
    {
        $ultimaInspecao = $record?->pneu?->inspecoes?->first();

        return [
            'pneu_id' => $record?->pneu_id,
            'pneu_ciclo_id' => $record?->pneu_ciclo_id,
            'veiculo_id' => $record?->veiculo_id,
            'pneu_posicao_veiculo_id' => $record?->id,
            'pneu_info' => ($record?->pneu?->numero_fogo ?? 'N/A').' - '.($record?->pneu?->marcaCatalogo?->nome ?? 'N/A').' / '.($record?->pneu?->modeloCatalogo?->nome ?? 'N/A'),
            'veiculo_info' => $this->getRecord()->placa,
            'posicao_info' => $record ? ($record->eixo.'º eixo / '.$record->posicao) : 'N/A',
            'medida_info' => $record?->pneu?->medidaCatalogo?->codigo ?? 'N/A',
            'ciclo_info' => (string) ($record?->pneu?->ciclo_vida ?? 'N/A'),
            'km_info' => number_format($record?->km_rodado ?? 0, 0, ',', '.'),
            'ultima_inspecao_info' => $ultimaInspecao?->data_inspecao?->format('d/m/Y') ?? 'Sem registro',
            'ultimo_resultado_info' => $ultimaInspecao?->resultado?->value ?? 'N/A',
            'ultimo_sulco_info' => $ultimaInspecao?->sulco_interno !== null
                ? number_format((float) $ultimaInspecao->sulco_interno, 2, ',', '.')
                : 'Sem registro',
            'tipo' => TipoInspecaoPneuEnum::CAMPO->value,
            'resultado' => null,
            'data_inspecao' => now()->toDateString(),
            'km_referencia' => $this->getRecord()->quilometragem_atual,
            'apto_recapagem' => null,
            'observacao' => null,
            'anexos' => [],
        ];
    }
}
