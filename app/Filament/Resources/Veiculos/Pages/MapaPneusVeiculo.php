<?php

namespace App\Filament\Resources\Veiculos\Pages;

use App\Enum\Pneu\TipoInspecaoPneuEnum;
use App\Filament\Resources\PneuInspecoes\Schemas\PneuInspecaoForm;
use App\Filament\Resources\Veiculos\VeiculoResource;
use App\Models\PneuInspecao;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema;

class MapaPneusVeiculo extends Page implements HasForms
{
    use InteractsWithForms;
    use InteractsWithRecord;

    protected static string $resource = VeiculoResource::class;

    protected static ?string $title = 'Mapa de Pneus';

    protected string $view = 'filament.resources.veiculos.pages.mapa-pneus-veiculo';

    public ?array $inspecaoData = [];

    public bool $isInspecaoSlideOverOpen = false;

    public ?int $selectedPosicaoId = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->form->fill([]);
    }

    public function form(Schema $schema): Schema
    {
        return PneuInspecaoForm::configure($schema)
            ->statePath('inspecaoData');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('voltar')
                ->label('Voltar ao Veículo')
                ->url(fn () => VeiculoResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    public function abrirInspecao(int $posicaoId): void
    {
        $posicao = $this->getPosicoes()->firstWhere('id', $posicaoId);

        if (! $posicao?->pneu_id) {
            Notification::make()
                ->title('Posição sem pneu aplicado')
                ->warning()
                ->send();

            return;
        }

        $this->selectedPosicaoId = $posicaoId;
        $this->isInspecaoSlideOverOpen = true;

        $this->form->fill([
            'pneu_id' => $posicao->pneu_id,
            'pneu_ciclo_id' => $posicao->pneu_ciclo_id,
            'veiculo_id' => $posicao->veiculo_id,
            'pneu_posicao_veiculo_id' => $posicao->id,
            'tipo' => TipoInspecaoPneuEnum::CAMPO->value,
            'resultado' => null,
            'data_inspecao' => now()->toDateString(),
            'km_referencia' => $this->record->quilometragem_atual,
            'parceiro_id' => null,
            'apto_recapagem' => null,
            'observacao' => null,
            'anexos' => [],
        ]);
    }

    public function fecharInspecao(): void
    {
        $this->isInspecaoSlideOverOpen = false;
        $this->selectedPosicaoId = null;
        $this->form->fill([]);
    }

    public function salvarInspecao(): void
    {
        $data = $this->form->getState();

        PneuInspecao::create($data);

        Notification::make()
            ->title('Inspeção registrada com sucesso')
            ->success()
            ->send();

        $this->record->refresh();
        $this->fecharInspecao();
    }

    public function getPosicoes(): \Illuminate\Support\Collection
    {
        return $this->record->pneus()
            ->with([
                'pneu.cicloAtual',
                'pneu.marcaCatalogo',
                'pneu.modeloCatalogo',
                'pneu.medidaCatalogo',
                'pneu.localCatalogo',
                'pneu.inspecoes' => fn ($query) => $query->latest('data_inspecao')->latest('id'),
            ])
            ->orderBy('sequencia')
            ->get();
    }

    public function getMapaPorEixo(): array
    {
        return $this->getPosicoes()
            ->groupBy('eixo')
            ->sortKeys()
            ->map(fn ($grupo) => $grupo->sortBy('sequencia')->values())
            ->all();
    }

    public function getSelectedPosicao()
    {
        if (! $this->selectedPosicaoId) {
            return null;
        }

        return $this->getPosicoes()->firstWhere('id', $this->selectedPosicaoId);
    }

    public function getUltimaInspecaoResumo($pneu): ?PneuInspecao
    {
        return $pneu?->inspecoes?->first();
    }
}
