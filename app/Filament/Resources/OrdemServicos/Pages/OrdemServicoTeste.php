<?php

namespace App\Filament\Resources\OrdemServicos\Pages;

use App\Enum\Agendamento\CategoriaAgendamentoEnum;
use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Filament\Oficina\Resources\OrdemServicos\Tables\OrdemServicosTable as OficinaOrdemServicosTable;
use App\Filament\Resources\Agendamentos\AgendamentoResource;
use App\Filament\Resources\Agendamentos\Schemas\AgendamentoForm;
use App\Filament\Resources\OrdemServicos\Actions;
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Models\Agendamento;
use App\Models\ManutencaoLancamento;
use App\Services\Agendamento\AgendamentoHistoricoService;
use App\Services\Agendamento\AgendamentoService;
use App\Services\Manutencao\ManutencaoLancamentoVinculoService;
use App\Services\NotificacaoService as notify;
use App\Services\PlanoManutencao\RelatorioPlanoManutencaoService;
use App\Services\Servico\ServicoCacheService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Size;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class OrdemServicoTeste extends Page implements HasSchemas
{
    use InteractsWithRecord;
    use InteractsWithSchemas;

    private const KM_BASE_RELATORIO_MANUTENCAO = 5000;

    protected static string $resource = OrdemServicoResource::class;

    protected static ?string $title = 'Ordem de Serviço';

    protected string $view = 'filament.resources.ordem-servicos.pages.ordem-servico-teste';

    public string $agendamentoBusca = '';

    public bool $showEditAgendamentoModal = false;

    public bool $showCreateAgendamentoModal = false;

    public ?int $editingAgendamentoId = null;

    public ?int $reagendandoItemServicoId = null;

    public ?array $editAgendamentoData = [];

    public ?array $createAgendamentoData = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->loadRecordRelations($this->resolveRecord($record));
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Actions\VincularServicoOrdemServicoAction::make($this->record)
                    ->size(Size::ExtraSmall),
                Action::make('novo_agendamento')
                    ->label('Novo Agendamento')
                    ->icon('heroicon-o-calendar-days')
                    ->size(Size::ExtraSmall)
                    ->action(fn () => $this->openCreateAgendamentoModal()),
                Actions\VincularPlanoPreventivoAction::make($this->record->id, $this->record->veiculo_id)
                    ->size(Size::ExtraSmall),
                Actions\VincularOrdemSankhyaAction::make()
                    ->size(Size::ExtraSmall),
                Actions\EncerrarOrdemServicoAction::make()
                    ->size(Size::ExtraSmall)
                    ->successRedirectUrl(fn (Model $record): string => OrdemServicoResource::getUrl()),
                Actions\PdfOrdemServicoAction::make()
                    ->size(Size::ExtraSmall),
                OficinaOrdemServicosTable::servicosAction()
                    ->size(Size::ExtraSmall),
                OficinaOrdemServicosTable::relatorioAction()
                    ->size(Size::ExtraSmall),
                DeleteAction::make('delete')
                    ->size(Size::ExtraSmall)
                    ->requiresConfirmation()
                    ->action(fn () => $this->record->delete()),
            ])->label('Ordem de Serviço')->button()->size(Size::ExtraSmall),
            ActionGroup::make([
                OficinaOrdemServicosTable::iniciarAction()
                    ->size(Size::ExtraSmall),
                OficinaOrdemServicosTable::encerrarAction()
                    ->size(Size::ExtraSmall),
                OficinaOrdemServicosTable::ajustarHorariosAction()
                    ->size(Size::ExtraSmall),
                OficinaOrdemServicosTable::removerApontamentoAbertoAction()
                    ->size(Size::ExtraSmall),
            ])->label('Apontamentos')->button()->size(Size::ExtraSmall),
        ];
    }

    public function editAgendamentoForm(Schema $schema): Schema
    {
        return AgendamentoForm::configure($schema)
            ->statePath('editAgendamentoData')
            ->model(Agendamento::class);
    }

    public function createAgendamentoForm(Schema $schema): Schema
    {
        return AgendamentoForm::configure($schema)
            ->statePath('createAgendamentoData')
            ->model(Agendamento::class);
    }

    public function openCreateAgendamentoModal(): void
    {
        $this->reagendandoItemServicoId = null;
        $this->createAgendamentoData = [
            'veiculo_id' => $this->record->veiculo_id,
            'data_agendamento' => null,
            'data_limite' => null,
            'servico_id' => null,
            'controla_posicao' => false,
            'posicao' => null,
            'plano_preventivo_id' => null,
            'observacao' => null,
            'parceiro_id' => $this->record->parceiro_id,
        ];

        $this->createAgendamentoForm->fill($this->createAgendamentoData);
        $this->showCreateAgendamentoModal = true;
    }

    #[On('open-reagendar-servico-modal')]
    public function openReagendarServicoModal(int $itemId): void
    {
        $item = $this->record->itens()->with('servico')->find($itemId);

        if (! $item) {
            notify::error(mensagem: 'Serviço não encontrado para reagendamento.');

            return;
        }

        if ($item->status !== StatusOrdemServicoEnum::PENDENTE) {
            notify::error(mensagem: 'Apenas serviços pendentes podem ser reagendados.');

            return;
        }

        $this->reagendandoItemServicoId = $item->id;
        $this->createAgendamentoData = [
            'veiculo_id' => $this->record->veiculo_id,
            'data_agendamento' => null,
            'data_limite' => null,
            'servico_id' => $item->servico_id,
            'controla_posicao' => (bool) $item->servico?->controla_posicao,
            'posicao' => $item->posicao,
            'plano_preventivo_id' => $item->plano_preventivo_id,
            'observacao' => $item->observacao,
            'parceiro_id' => $this->record->parceiro_id,
        ];

        $this->createAgendamentoForm->fill($this->createAgendamentoData);
        $this->showCreateAgendamentoModal = true;
    }

    public function closeCreateAgendamentoModal(): void
    {
        $this->showCreateAgendamentoModal = false;
        $this->reagendandoItemServicoId = null;
        $this->createAgendamentoData = [];
    }

    public function saveCreateAgendamento(): void
    {
        $data = $this->createAgendamentoForm->getState();

        if ($this->reagendandoItemServicoId) {
            $item = $this->record->itens()->find($this->reagendandoItemServicoId);

            if (! $item || $item->status !== StatusOrdemServicoEnum::PENDENTE) {
                notify::error(mensagem: 'Serviço não disponível para reagendamento.');
                $this->closeCreateAgendamentoModal();

                return;
            }

            $data['categoria'] = CategoriaAgendamentoEnum::REAGENDAMENTO;
        }

        $service = new AgendamentoService;
        $service->create($data);

        if ($service->hasError()) {
            notify::error(mensagem: $service->getMessage());

            return;
        }

        if (isset($item)) {
            $item->update(['status' => StatusOrdemServicoEnum::ADIADO]);
        }

        notify::success(mensagem: $this->reagendandoItemServicoId ? 'Serviço reagendado com sucesso!' : 'Agendamento criado com sucesso!');
        $this->closeCreateAgendamentoModal();
        $this->record = $this->loadRecordRelations($this->record->fresh());
        $this->dispatch('ordem-servico-atualizada');
    }

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
        $this->record = $this->loadRecordRelations($this->record->fresh());
    }

    public function getAgendamentoEditUrl(int $agendamentoId): string
    {
        return AgendamentoResource::getUrl('edit', ['record' => $agendamentoId]);
    }

    public function openEditAgendamentoModal(int $agendamentoId): void
    {
        $agendamento = Agendamento::query()
            ->where('veiculo_id', $this->record->veiculo_id)
            ->where('status', StatusOrdemServicoEnum::PENDENTE)
            ->find($agendamentoId);

        if (! $agendamento) {
            notify::error(mensagem: 'Agendamento não encontrado.');

            return;
        }

        $this->editingAgendamentoId = $agendamento->id;
        $this->editAgendamentoData = [
            'veiculo_id' => $agendamento->veiculo_id,
            'data_agendamento' => $agendamento->data_agendamento?->format('Y-m-d'),
            'data_limite' => $agendamento->data_limite?->format('Y-m-d'),
            'servico_id' => $agendamento->servico_id,
            'controla_posicao' => ServicoCacheService::controlaPosicao($agendamento->servico_id),
            'posicao' => $agendamento->posicao,
            'plano_preventivo_id' => $agendamento->plano_preventivo_id,
            'observacao' => $agendamento->observacao,
            'parceiro_id' => $agendamento->parceiro_id,
        ];

        $this->editAgendamentoForm->fill($this->editAgendamentoData);
        $this->showEditAgendamentoModal = true;
    }

    public function closeEditAgendamentoModal(): void
    {
        $this->showEditAgendamentoModal = false;
        $this->editingAgendamentoId = null;
        $this->editAgendamentoData = [];
    }

    public function saveEditAgendamento(): void
    {
        $agendamento = Agendamento::query()
            ->where('veiculo_id', $this->record->veiculo_id)
            ->where('status', StatusOrdemServicoEnum::PENDENTE)
            ->find($this->editingAgendamentoId);

        if (! $agendamento) {
            notify::error(mensagem: 'Agendamento não encontrado para edição.');

            $this->closeEditAgendamentoModal();

            return;
        }

        $data = $this->editAgendamentoForm->getState();
        $controlaPosicao = ServicoCacheService::controlaPosicao($data['servico_id']);
        $posicao = $controlaPosicao ? ($data['posicao'] ?? $agendamento->posicao) : null;

        if ($controlaPosicao && blank($posicao)) {
            notify::error(mensagem: 'Selecione a posição para este serviço antes de salvar.');

            return;
        }

        $antes = [
            'veiculo_id' => $agendamento->veiculo_id,
            'data_agendamento' => optional($agendamento->data_agendamento)->format('Y-m-d'),
            'data_limite' => optional($agendamento->data_limite)->format('Y-m-d'),
            'servico_id' => $agendamento->servico_id,
            'posicao' => $agendamento->posicao,
            'plano_preventivo_id' => $agendamento->plano_preventivo_id,
            'observacao' => $agendamento->observacao,
            'parceiro_id' => $agendamento->parceiro_id,
        ];

        $agendamento->update([
            'veiculo_id' => $data['veiculo_id'],
            'data_agendamento' => $data['data_agendamento'] ?? null,
            'data_limite' => $data['data_limite'] ?? null,
            'servico_id' => $data['servico_id'],
            'posicao' => $posicao,
            'plano_preventivo_id' => $data['plano_preventivo_id'] ?? null,
            'observacao' => $data['observacao'] ?? null,
            'parceiro_id' => $data['parceiro_id'] ?? null,
        ]);

        app(AgendamentoHistoricoService::class)->registrarAlteracoes(
            agendamento: $agendamento,
            tipoEvento: 'EDITADO',
            antes: $antes,
            depois: [
                'veiculo_id' => $agendamento->veiculo_id,
                'data_agendamento' => optional($agendamento->data_agendamento)->format('Y-m-d'),
                'data_limite' => optional($agendamento->data_limite)->format('Y-m-d'),
                'servico_id' => $agendamento->servico_id,
                'posicao' => $agendamento->posicao,
                'plano_preventivo_id' => $agendamento->plano_preventivo_id,
                'observacao' => $agendamento->observacao,
                'parceiro_id' => $agendamento->parceiro_id,
            ],
            descricao: 'Agendamento editado pela tela de OS.',
            userId: Auth::id(),
        );

        notify::success(mensagem: 'Agendamento atualizado com sucesso!');
        $this->closeEditAgendamentoModal();
        $this->record = $this->loadRecordRelations($this->record->fresh());
    }

    public function getAgendamentosVeiculoProperty(): Collection
    {
        return $this->record->agendamentosPendentes
            ->filter(function (Agendamento $agendamento): bool {
                if (blank($this->agendamentoBusca)) {
                    return true;
                }

                $needle = mb_strtolower(trim($this->agendamentoBusca));
                $haystack = mb_strtolower(implode(' ', array_filter([
                    $agendamento->servico?->descricao,
                    $agendamento->parceiro?->nome,
                    $agendamento->observacao,
                    $agendamento->categoria?->value,
                ])));

                return str_contains($haystack, $needle);
            })
            ->sortBy(fn (Agendamento $agendamento) => sprintf(
                '%s-%s-%010d',
                optional($agendamento->data_agendamento)->format('Ymd') ?? '99999999',
                optional($agendamento->data_limite)->format('Ymd') ?? '99999999',
                $agendamento->id,
            ))
            ->values();
    }

    public function getAgendamentosDestaOsProperty(): Collection
    {
        return $this->record->agendamentos
            ->sortByDesc(fn (Agendamento $agendamento) => sprintf(
                '%s-%010d',
                optional($agendamento->updated_at)->format('YmdHis') ?? '00000000000000',
                $agendamento->id,
            ))
            ->values();
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
        $this->record = $this->loadRecordRelations($this->record->fresh());
    }

    public function vincularLancamento(int $lancamentoId): void
    {
        $lancamento = ManutencaoLancamento::query()
            ->where('veiculo_id', $this->record->veiculo_id)
            ->find($lancamentoId);

        if (! $lancamento) {
            notify::error(mensagem: 'Lançamento de manutenção não encontrado.');

            return;
        }

        app(ManutencaoLancamentoVinculoService::class)->vincular($lancamento, $this->record, 'manual');

        notify::success(mensagem: 'Custo vinculado com sucesso!');
        $this->record = $this->loadRecordRelations($this->record->fresh());
    }

    public function desvincularLancamento(int $lancamentoId): void
    {
        $lancamento = ManutencaoLancamento::query()
            ->where('ordem_servico_id', $this->record->id)
            ->find($lancamentoId);

        if (! $lancamento) {
            notify::error(mensagem: 'Lançamento vinculado não encontrado.');

            return;
        }

        app(ManutencaoLancamentoVinculoService::class)->desvincular($lancamento);

        notify::success(mensagem: 'Vínculo removido com sucesso!');
        $this->record = $this->loadRecordRelations($this->record->fresh());
    }

    public function dispensarLancamento(int $lancamentoId): void
    {
        $lancamento = ManutencaoLancamento::query()
            ->where('veiculo_id', $this->record->veiculo_id)
            ->whereNull('ordem_servico_id')
            ->where('dispensado_vinculo', false)
            ->find($lancamentoId);

        if (! $lancamento) {
            notify::error(mensagem: 'Lançamento pendente não encontrado.');

            return;
        }

        app(ManutencaoLancamentoVinculoService::class)->dispensar($lancamento);

        notify::success(mensagem: 'Lançamento dispensado com sucesso!');
        $this->record = $this->loadRecordRelations($this->record->fresh());
    }

    public function getLancamentosPendentesProperty()
    {
        return ManutencaoLancamento::query()
            ->where('veiculo_id', $this->record->veiculo_id)
            ->whereNull('ordem_servico_id')
            ->where('dispensado_vinculo', false)
            ->orderByDesc('data_negociacao')
            ->orderByDesc('id')
            ->limit(15)
            ->get();
    }

    public function getPlanosManutencaoProperty(): \Illuminate\Support\Collection
    {
        return collect(app(RelatorioPlanoManutencaoService::class)->obterDadosRelatorio([
            'veiculo_id' => $this->record->veiculo_id,
            'km_restante_maximo' => self::KM_BASE_RELATORIO_MANUTENCAO,
        ]));
    }

    public function getKmBaseRelatorioManutencaoProperty(): int
    {
        return self::KM_BASE_RELATORIO_MANUTENCAO;
    }

    protected function loadRecordRelations(Model $record): Model
    {
        return $record->load([
            'agendamentos.servico:id,descricao',
            'agendamentos.parceiro:id,nome',
            'agendamentosPendentes.servico:id,descricao',
            'agendamentosPendentes.parceiro:id,nome',
            'planoPreventivoVinculado.planoPreventivo:id,descricao,intervalo',
            'manutencaoLancamentos',
            'apontamentosAbertosOficina.colaborador:id,codigo,nome',
            'apontamentosOficina.colaborador:id,codigo,nome',
            'apontamentosOficina.itens.servico:id,codigo,descricao',
        ]);
    }
}
