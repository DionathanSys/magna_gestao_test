<?php

namespace App\Livewire;

use App\Enum;
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Filament\Resources\OrdemServicos\Schemas\Components;
use App\Filament\Resources\OrdemServicos\Schemas\ItemOrdemServicoForm;
use App\Filament\Resources\OrdemServicos\Schemas\OrdemServicoForm;
use App\Models\Agendamento;
use App\Models\ItemOrdemServico;
use App\Models\ManutencaoLancamento;
use App\Models\OrdemServico;
use App\Models\Veiculo;
use App\Services\Agendamento\AgendamentoService;
use App\Services\ItemOrdemServico\ItemOrdemServicoService;
use App\Services\Manutencao\ManutencaoLancamentoVinculoService;
use App\Services\NotificacaoService as notify;
use App\Services\OrdemServico\OrdemServicoService;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Concerns\InteractsWithRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;

class FormOsMobile extends Component implements HasSchemas
{
    use InteractsWithActions;
    use InteractsWithRecord;
    use InteractsWithSchemas;

    public ?array $data = [];

    public ?int $ordemServicoId = null;

    public ?OrdemServico $ordemServico = null;

    public bool $isEditing = false;

    public ?string $activeTab = 'servicos';

    public function mount(?int $ordemServicoId = null): void
    {
        $this->isEditing = $ordemServicoId !== null;

        if ($this->isEditing) {
            $this->ordemServicoId = $ordemServicoId;
            $this->loadRecord();

            if (! $this->ordemServico) {
                notify::error(mensagem: 'Ordem de Serviço não encontrada.');
                $this->redirect(route('os-mobile.create'));

                return;
            }

            $this->form->fill($this->ordemServico->attributesToArray());
        } else {
            $this->form->fill([
                'tipo_manutencao' => Enum\OrdemServico\TipoManutencaoEnum::CORRETIVA->value,
                'status' => Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE->value,
                'data_inicio' => now()->format('Y-m-d H:i:s'),
            ]);
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(1)
                    ->schema([
                        Components\OrdemServicoVeiculoInput::make()
                            ->columnSpanFull(),
                        OrdemServicoForm::getQuilometragemFormField()
                            ->label('Quilometragem')
                            ->columnSpanFull(),
                        Components\OrdemServicoTipoManutencaoInput::make()
                            ->columnSpanFull(),
                        OrdemServicoForm::getStatusFormField()
                            ->columnSpanFull()
                            ->visible($this->isEditing),
                        OrdemServicoForm::getStatusSankhyaFormField()
                            ->label('Sankhya')
                            ->columnSpanFull()
                            ->visible($this->isEditing),
                        OrdemServicoForm::getParceiroIdFormField()
                            ->label('Parceiro Externo')
                            ->columnSpanFull()
                            ->visible($this->isEditing),
                        OrdemServicoForm::getDataFimFormField()
                            ->columnSpanFull()
                            ->visible($this->isEditing),
                    ]),
            ])
            ->statePath('data')
            ->model($this->ordemServico ?? OrdemServico::class);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (! $this->isEditing) {
            $veiculo = Veiculo::with('kmAtual')->find($data['veiculo_id']);

            if (($veiculo->kmAtual->quilometragem ?? 0) > $data['quilometragem']) {
                notify::error(mensagem: 'A quilometragem informada deve ser maior ou igual à quilometragem atual do veículo.');

                return;
            }

            $data['created_by'] = auth()->id();
            $data['status'] = Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE;
            $data['status_sankhya'] = Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE;

            $this->ordemServico = OrdemServico::create($data);
            notify::success(mensagem: 'Ordem de Serviço criada com sucesso!');

            $this->redirect(route('os-mobile.edit', $this->ordemServico->id));
        } else {
            $this->ordemServico->update($data);
            notify::success(mensagem: 'Ordem de Serviço atualizada com sucesso!');
            $this->loadRecord();
        }
    }

    public function salvarForm(): void
    {
        $this->save();
    }

    // ── Item Serviço Actions ──────────────────────────────

    public bool $showFormServico = false;

    public ?array $formDataServico = [];

    public ?int $editandoItemServicoId = null;

    public function toggleFormServico(): void
    {
        $this->showFormServico = ! $this->showFormServico;
        $this->editandoItemServicoId = null;
        $this->formDataServico = [];
    }

    public function formServico(Schema $schema): Schema
    {
        return $schema
            ->schema(ItemOrdemServicoForm::configure($schema, includeStatus: true))
            ->statePath('formDataServico')
            ->model(ItemOrdemServico::class);
    }

    public function salvarServico(): void
    {
        $service = new ItemOrdemServicoService;

        if ($this->editandoItemServicoId) {
            $service->update($this->editandoItemServicoId, $this->formDataServico);

            if ($service->hasError()) {
                notify::error(mensagem: $service->getMessage());

                return;
            }

            notify::success(mensagem: 'Serviço atualizado com sucesso!');
        } else {
            $this->formDataServico['ordem_servico_id'] = $this->ordemServico->id;
            $service->create($this->formDataServico);

            if ($service->hasError()) {
                notify::error(mensagem: $service->getMessage());

                return;
            }

            notify::success(mensagem: 'Serviço vinculado com sucesso!');
        }

        $this->showFormServico = false;
        $this->editandoItemServicoId = null;
        $this->formDataServico = [];
        $this->loadRecord();
    }

    public function editarServico(int $itemServicoId): void
    {
        $item = ItemOrdemServico::find($itemServicoId);

        if (! $item) {
            notify::error(mensagem: 'Serviço não encontrado.');

            return;
        }

        $this->editandoItemServicoId = $itemServicoId;
        $this->formDataServico = $item->toArray();
        $this->showFormServico = true;
    }

    public function excluirServico(int $itemServicoId): void
    {
        $item = ItemOrdemServico::find($itemServicoId);

        if (! $item) {
            notify::error(mensagem: 'Serviço não encontrado.');

            return;
        }

        $service = new \App\Services\OrdemServico\ItemOrdemServicoService;
        $service->delete($item);

        notify::success(mensagem: 'Serviço removido com sucesso!');
        $this->loadRecord();
    }

    // ── Agendamento Actions ──────────────────────────────

    public function vincularAgendamento(int $agendamentoId): void
    {
        $agendamento = Agendamento::query()->find($agendamentoId);

        if (! $agendamento) {
            notify::error(mensagem: 'Agendamento não encontrado.');

            return;
        }

        $service = new AgendamentoService;
        $service->vincularEmOrdemServico($agendamento);

        if ($service->hasError()) {
            notify::error(mensagem: $service->getMessage());

            return;
        }

        notify::success(mensagem: 'Agendamento vinculado com sucesso!');
        $this->loadRecord();
    }

    public function cancelarAgendamento(int $agendamentoId): void
    {
        $agendamento = Agendamento::query()->find($agendamentoId);

        if (! $agendamento) {
            notify::error(mensagem: 'Agendamento não encontrado.');

            return;
        }

        $service = new AgendamentoService;
        $service->cancelar($agendamento);

        if ($service->hasError()) {
            notify::error(mensagem: $service->getMessage());

            return;
        }

        notify::success(mensagem: 'Agendamento cancelado com sucesso!');
        $this->loadRecord();
    }

    // ── Lancamento Actions ──────────────────────────────

    public function vincularLancamento(int $lancamentoId): void
    {
        $lancamento = ManutencaoLancamento::query()
            ->where('veiculo_id', $this->ordemServico->veiculo_id)
            ->find($lancamentoId);

        if (! $lancamento) {
            notify::error(mensagem: 'Lançamento de manutenção não encontrado.');

            return;
        }

        app(ManutencaoLancamentoVinculoService::class)->vincular($lancamento, $this->ordemServico, 'manual');

        notify::success(mensagem: 'Custo vinculado com sucesso!');
        $this->loadRecord();
    }

    public function desvincularLancamento(int $lancamentoId): void
    {
        $lancamento = ManutencaoLancamento::query()
            ->where('ordem_servico_id', $this->ordemServico->id)
            ->find($lancamentoId);

        if (! $lancamento) {
            notify::error(mensagem: 'Lançamento vinculado não encontrado.');

            return;
        }

        app(ManutencaoLancamentoVinculoService::class)->desvincular($lancamento);

        notify::success(mensagem: 'Vínculo removido com sucesso!');
        $this->loadRecord();
    }

    // ── Encerrar ─────────────────────────────────────────

    public function encerrar(): void
    {
        $service = new OrdemServicoService;
        $service->encerrarOrdemServico($this->ordemServico);

        if ($service->hasError()) {
            notify::error(mensagem: $service->getMessage());

            return;
        }

        notify::success(mensagem: 'Ordem de Serviço encerrada com sucesso!');
        $this->loadRecord();
    }

    // ── Helpers ──────────────────────────────────────────

    public function getPdfUrl(): string
    {
        return route('ordem-servico.pdf.visualizar', $this->ordemServico->id);
    }

    public function getDesktopUrl(): string
    {
        return OrdemServicoResource::getUrl('custom', ['record' => $this->ordemServico->id]);
    }

    public function getLancamentosPendentesProperty()
    {
        return ManutencaoLancamento::query()
            ->where('veiculo_id', $this->ordemServico->veiculo_id)
            ->whereNull('ordem_servico_id')
            ->orderByDesc('data_negociacao')
            ->orderByDesc('id')
            ->limit(15)
            ->get();
    }

    protected function loadRecord(): void
    {
        if (! $this->ordemServico) {
            return;
        }

        $this->ordemServico = $this->ordemServico->fresh()->load([
            'itens.servico:id,codigo,descricao',
            'itens.comentarios',
            'agendamentosPendentes.servico:id,descricao',
            'agendamentosPendentes.parceiro:id,nome',
            'planoPreventivoVinculado.planoPreventivo:id,descricao,intervalo',
            'manutencaoLancamentos',
        ]);
    }

    public function render()
    {
        return view('livewire.form-os-mobile');
    }
}
