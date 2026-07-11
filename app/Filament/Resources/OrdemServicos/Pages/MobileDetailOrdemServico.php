<?php

namespace App\Filament\Resources\OrdemServicos\Pages;

use App\Enum\Agendamento\CategoriaAgendamentoEnum;
use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Filament\Resources\Agendamentos\AgendamentoResource;
use App\Filament\Resources\Agendamentos\Schemas\AgendamentoForm;
use App\Filament\Resources\OrdemServicos\Actions;
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Filament\Resources\OrdemServicos\Schemas\Components\OrdemServicoTipoManutencaoInput;
use App\Filament\Resources\OrdemServicos\Schemas\Components\OrdemServicoVeiculoInput;
use App\Filament\Resources\OrdemServicos\Schemas\ItemOrdemServicoForm;
use App\Filament\Resources\OrdemServicos\Schemas\OrdemServicoForm;
use App\Filament\Resources\Servicos\Schemas\ServicoForm;
use App\Models\Agendamento;
use App\Models\ItemOrdemServico;
use App\Models\ManutencaoLancamento;
use App\Models\OrdemServico;
use App\Models\Servico;
use App\Services\Agendamento\AgendamentoService;
use App\Services\ItemOrdemServico\ItemOrdemServicoService;
use App\Services\Manutencao\ManutencaoLancamentoVinculoService;
use App\Services\NotificacaoService as notify;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class MobileDetailOrdemServico extends Page implements HasSchemas
{
    use InteractsWithRecord;
    use InteractsWithSchemas;

    protected static string $resource = OrdemServicoResource::class;

    protected static ?string $title = 'OS';

    protected string $view = 'filament.resources.ordem-servicos.pages.mobile-detail';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public ?string $activeTab = 'servicos';

    public string $agendamentoBusca = '';

    public bool $showFormServico = false;

    public bool $showFormNovoServico = false;

    public bool $showFormAgendamento = false;

    public ?array $formDataServico = [];

    public ?array $formDataNovoServico = [];

    public ?array $formDataAgendamento = [];

    public ?int $editandoItemServicoId = null;

    public ?int $reagendandoItemServicoId = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->loadRecordRelations($this->resolveRecord($record));
        $this->form->fill($this->record->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Grid::make(1)
                    ->schema([
                        OrdemServicoVeiculoInput::make()
                            ->columnSpanFull(),
                        OrdemServicoForm::getQuilometragemFormField()
                            ->label('Quilometragem')
                            ->columnSpanFull(),
                        OrdemServicoTipoManutencaoInput::make()
                            ->columnSpanFull(),
                        OrdemServicoForm::getStatusFormField()
                            ->columnSpanFull(),
                        OrdemServicoForm::getStatusSankhyaFormField()
                            ->label('Sankhya')
                            ->columnSpanFull(),
                        OrdemServicoForm::getParceiroIdFormField()
                            ->label('Parceiro Externo')
                            ->columnSpanFull(),
                        OrdemServicoForm::getDataFimFormField()
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data')
            ->model($this->record);
    }

    public function formServico(Schema $schema): Schema
    {
        return ItemOrdemServicoForm::configure($schema, includeStatus: true)
            ->statePath('formDataServico')
            ->model(ItemOrdemServico::class);
    }

    public function formNovoServico(Schema $schema): Schema
    {
        return ServicoForm::configure($schema)
            ->statePath('formDataNovoServico')
            ->model(Servico::class);
    }

    public function formAgendamento(Schema $schema): Schema
    {
        return AgendamentoForm::configure($schema)
            ->statePath('formDataAgendamento')
            ->model(Agendamento::class);
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    // ── OS Form Actions ──────────────────────────────────

    public function salvarForm(): void
    {
        $data = $this->form->getState();
        $this->record->update($data);
        notify::success(mensagem: 'Ordem de Serviço atualizada com sucesso!');
        $this->refreshRecord();
    }

    public function encerrarAction(): Action
    {
        return Actions\EncerrarOrdemServicoAction::make($this->record)
            ->after(function () {
                $this->refreshRecord();
            });
    }

    public function excluirOrdemServico(): void
    {
        $this->record->delete();

        notify::success(mensagem: 'Ordem de Serviço excluída com sucesso!');

        $this->redirect(OrdemServicoResource::getUrl('mobile-list'));
    }

    // ── Item Serviço Actions ──────────────────────────────

    public function toggleFormServico(): void
    {
        $this->showFormServico = ! $this->showFormServico;
        $this->editandoItemServicoId = null;
        $this->formDataServico = [];

        if ($this->showFormServico) {
            $this->formServico->fill([
                'servico_id' => null,
                'controla_posicao' => false,
                'posicao' => null,
                'observacao' => null,
                'status' => StatusOrdemServicoEnum::PENDENTE->value,
            ]);
        }
    }

    public function toggleFormNovoServico(): void
    {
        $this->showFormNovoServico = ! $this->showFormNovoServico;
        $this->formDataNovoServico = [];

        if ($this->showFormNovoServico) {
            $this->formNovoServico->fill([
                'codigo' => null,
                'descricao' => null,
                'complemento' => null,
                'tipo' => null,
                'controla_posicao' => false,
                'is_active' => true,
            ]);
        }
    }

    public function salvarServico(): void
    {
        $data = $this->formServico->getState();

        $service = new ItemOrdemServicoService;

        if ($this->editandoItemServicoId) {
            $service->update($this->editandoItemServicoId, $data);

            if ($service->hasError()) {
                notify::error(mensagem: $service->getMessage());

                return;
            }

            notify::success(mensagem: 'Serviço atualizado com sucesso!');
        } else {
            $data['ordem_servico_id'] = $this->record->id;
            $service->create($data);

            if ($service->hasError()) {
                notify::error(mensagem: $service->getMessage());

                return;
            }

            notify::success(mensagem: 'Serviço vinculado com sucesso!');
        }

        $this->showFormServico = false;
        $this->editandoItemServicoId = null;
        $this->formDataServico = [];
        $this->refreshRecord();
    }

    public function salvarNovoServico(): void
    {
        $data = $this->formNovoServico->getState();

        $servico = Servico::query()->create($data);

        notify::success(mensagem: 'Serviço cadastrado com sucesso!');

        $this->showFormNovoServico = false;
        $this->formDataNovoServico = [];

        if (! $this->showFormServico) {
            $this->toggleFormServico();
        }

        $this->formServico->fill([
            'servico_id' => $servico->id,
            'controla_posicao' => (bool) $servico->controla_posicao,
            'posicao' => null,
            'observacao' => null,
            'status' => StatusOrdemServicoEnum::PENDENTE->value,
        ]);
    }

    public function abrirNovoAgendamento(): void
    {
        $this->reagendandoItemServicoId = null;
        $this->showFormAgendamento = true;
        $this->activeTab = 'agendamentos';
        $this->formDataAgendamento = [
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

        $this->formAgendamento->fill($this->formDataAgendamento);
    }

    public function reagendarServico(int $itemServicoId): void
    {
        $item = $this->record->itens()->with('servico')->find($itemServicoId);

        if (! $item) {
            notify::error(mensagem: 'Serviço não encontrado para reagendamento.');

            return;
        }

        if ($item->status !== StatusOrdemServicoEnum::PENDENTE) {
            notify::error(mensagem: 'Apenas serviços pendentes podem ser reagendados.');

            return;
        }

        $this->reagendandoItemServicoId = $item->id;
        $this->showFormAgendamento = true;
        $this->activeTab = 'agendamentos';
        $this->formDataAgendamento = [
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

        $this->formAgendamento->fill($this->formDataAgendamento);
    }

    public function fecharFormAgendamento(): void
    {
        $this->showFormAgendamento = false;
        $this->reagendandoItemServicoId = null;
        $this->formDataAgendamento = [];
    }

    public function salvarAgendamento(): void
    {
        $data = $this->formAgendamento->getState();

        if ($this->reagendandoItemServicoId) {
            $item = $this->record->itens()->find($this->reagendandoItemServicoId);

            if (! $item || $item->status !== StatusOrdemServicoEnum::PENDENTE) {
                notify::error(mensagem: 'Serviço não disponível para reagendamento.');
                $this->fecharFormAgendamento();

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
        $this->fecharFormAgendamento();
        $this->refreshRecord();
    }

    public function editarServico(int $itemServicoId): void
    {
        $item = ItemOrdemServico::find($itemServicoId);

        if (! $item) {
            notify::error(mensagem: 'Serviço não encontrado.');

            return;
        }

        $this->editandoItemServicoId = $itemServicoId;
        $this->formDataServico = [
            'servico_id' => $item->servico_id,
            'controla_posicao' => (bool) $item->controla_posicao,
            'posicao' => $item->posicao,
            'observacao' => $item->observacao,
            'status' => $item->status?->value ?? $item->status,
        ];
        $this->formServico->fill($this->formDataServico);
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
        $this->refreshRecord();
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
        $this->refreshRecord();
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
        $this->refreshRecord();
    }

    // ── Lancamento Actions ──────────────────────────────

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
        $this->refreshRecord();
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
        $this->refreshRecord();
    }

    // ── Helpers ──────────────────────────────────────────

    public function getPdfUrl(): string
    {
        return route('ordem-servico.pdf.visualizar', $this->record->id);
    }

    public function getListUrl(): string
    {
        return OrdemServicoResource::getUrl('mobile-list');
    }

    public function getAgendamentoEditUrl(int $agendamentoId): string
    {
        return AgendamentoResource::getUrl('edit', ['record' => $agendamentoId]);
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

    public function getLancamentosPendentesProperty()
    {
        return ManutencaoLancamento::query()
            ->where('veiculo_id', $this->record->veiculo_id)
            ->whereNull('ordem_servico_id')
            ->orderByDesc('data_negociacao')
            ->orderByDesc('id')
            ->limit(15)
            ->get();
    }

    public function formatDateTime(mixed $value, string $format = 'd/m/Y H:i'): string
    {
        if (blank($value)) {
            return '—';
        }

        if ($value instanceof Carbon) {
            return $value->format($format);
        }

        return Carbon::parse($value)->format($format);
    }

    public function formatDate(mixed $value, string $format = 'd/m/Y'): string
    {
        return $this->formatDateTime($value, $format);
    }

    protected function refreshRecord(): void
    {
        $this->record = $this->loadRecordRelations($this->record->fresh());
    }

    protected function loadRecordRelations(OrdemServico $record): OrdemServico
    {
        return $record->load([
            'veiculo:id,placa',
            'itens.servico:id,codigo,descricao',
            'itens.comentarios',
            'agendamentos.servico:id,descricao',
            'agendamentos.parceiro:id,nome',
            'agendamentosPendentes.servico:id,descricao',
            'agendamentosPendentes.parceiro:id,nome',
            'planoPreventivoVinculado.planoPreventivo:id,descricao,intervalo',
            'manutencaoLancamentos',
        ]);
    }
}
