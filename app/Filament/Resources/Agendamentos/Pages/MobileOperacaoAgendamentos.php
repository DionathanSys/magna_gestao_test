<?php

namespace App\Filament\Resources\Agendamentos\Pages;

use App\Enum\Agendamento\CategoriaAgendamentoEnum;
use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Filament\Resources\Agendamentos\AgendamentoResource;
use App\Filament\Resources\Agendamentos\Schemas\AgendamentoForm;
use App\Models\Agendamento;
use App\Models\Servico;
use App\Services\Agendamento\AgendamentoHistoricoService;
use App\Services\Agendamento\AgendamentoService;
use App\Services\NotificacaoService as notify;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class MobileOperacaoAgendamentos extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string $resource = AgendamentoResource::class;

    protected static ?string $title = 'Agendamentos Mobile';

    protected string $view = 'filament.resources.agendamentos.pages.mobile-operacao-agendamentos';

    protected static bool $shouldRegisterNavigation = false;

    public string $activeTab = 'hoje';

    public string $busca = '';

    public bool $showReprogramarModal = false;

    public ?int $editingAgendamentoId = null;

    public ?array $reprogramarData = [];

    public bool $showCreateAgendamentoModal = false;

    public ?array $createAgendamentoData = [];

    public bool $showEditAgendamentoModal = false;

    public ?int $editingFullAgendamentoId = null;

    public ?array $editAgendamentoData = [];

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
        $this->createAgendamentoData = [
            'veiculo_id' => null,
            'data_agendamento' => null,
            'data_limite' => null,
            'servico_id' => null,
            'controla_posicao' => false,
            'posicao' => null,
            'plano_preventivo_id' => null,
            'observacao' => null,
            'parceiro_id' => null,
        ];

        $this->createAgendamentoForm->fill($this->createAgendamentoData);
        $this->showCreateAgendamentoModal = true;
    }

    public function closeCreateAgendamentoModal(): void
    {
        $this->showCreateAgendamentoModal = false;
        $this->createAgendamentoData = [];
    }

    public function saveCreateAgendamento(): void
    {
        $data = $this->createAgendamentoForm->getState();

        $service = new AgendamentoService;
        $service->create($data);

        if ($service->hasError()) {
            notify::error(mensagem: $service->getMessage());

            return;
        }

        notify::success(mensagem: 'Agendamento criado com sucesso!');
        $this->closeCreateAgendamentoModal();
    }

    public function reprogramarForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Reprogramar Agendamento')
                    ->schema([
                        DatePicker::make('data_agendamento')
                            ->label('Agendado para'),
                        DatePicker::make('data_limite')
                            ->label('Data limite'),
                        Textarea::make('observacao')
                            ->label('Observação')
                            ->rows(3)
                            ->maxLength(255),
                    ]),
            ])
            ->statePath('reprogramarData')
            ->model(Agendamento::class);
    }

    public function openReprogramarModal(int $agendamentoId): void
    {
        $agendamento = Agendamento::query()->find($agendamentoId);

        if (! $agendamento) {
            notify::error(mensagem: 'Agendamento não encontrado.');

            return;
        }

        $this->editingAgendamentoId = $agendamento->id;
        $this->reprogramarData = [
            'data_agendamento' => $agendamento->data_agendamento?->format('Y-m-d'),
            'data_limite' => $agendamento->data_limite?->format('Y-m-d'),
            'observacao' => $agendamento->observacao,
        ];

        $this->reprogramarForm->fill($this->reprogramarData);
        $this->showReprogramarModal = true;
    }

    public function closeReprogramarModal(): void
    {
        $this->showReprogramarModal = false;
        $this->editingAgendamentoId = null;
        $this->reprogramarData = [];
    }

    public function saveReprogramacao(): void
    {
        $agendamento = Agendamento::query()->find($this->editingAgendamentoId);

        if (! $agendamento) {
            notify::error(mensagem: 'Agendamento não encontrado para reprogramação.');
            $this->closeReprogramarModal();

            return;
        }

        $data = $this->reprogramarForm->getState();

        $antes = [
            'data_agendamento' => optional($agendamento->data_agendamento)->format('Y-m-d'),
            'data_limite' => optional($agendamento->data_limite)->format('Y-m-d'),
            'observacao' => $agendamento->observacao,
        ];

        $agendamento->update([
            'data_agendamento' => $data['data_agendamento'] ?? null,
            'data_limite' => $data['data_limite'] ?? null,
            'observacao' => $data['observacao'] ?? null,
            'updated_by' => Auth::id(),
        ]);

        app(AgendamentoHistoricoService::class)->registrarAlteracoes(
            agendamento: $agendamento,
            tipoEvento: 'REPROGRAMADO',
            antes: $antes,
            depois: [
                'data_agendamento' => optional($agendamento->data_agendamento)->format('Y-m-d'),
                'data_limite' => optional($agendamento->data_limite)->format('Y-m-d'),
                'observacao' => $agendamento->observacao,
            ],
            descricao: 'Agendamento reprogramado pela tela mobile.',
            userId: Auth::id(),
        );

        notify::success(mensagem: 'Agendamento reprogramado com sucesso!');
        $this->closeReprogramarModal();
    }

    public function openEditAgendamentoModal(int $agendamentoId): void
    {
        $agendamento = Agendamento::query()
            ->whereIn('status', [StatusOrdemServicoEnum::PENDENTE, StatusOrdemServicoEnum::EXECUCAO])
            ->find($agendamentoId);

        if (! $agendamento) {
            notify::error(mensagem: 'Agendamento não encontrado.');

            return;
        }

        $servico = Servico::query()->find($agendamento->servico_id);

        $this->editingFullAgendamentoId = $agendamento->id;
        $this->editAgendamentoData = [
            'veiculo_id' => $agendamento->veiculo_id,
            'data_agendamento' => $agendamento->data_agendamento?->format('Y-m-d'),
            'data_limite' => $agendamento->data_limite?->format('Y-m-d'),
            'servico_id' => $agendamento->servico_id,
            'controla_posicao' => (bool) $servico?->controla_posicao,
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
        $this->editingFullAgendamentoId = null;
        $this->editAgendamentoData = [];
    }

    public function saveEditAgendamento(): void
    {
        $agendamento = Agendamento::query()->find($this->editingFullAgendamentoId);

        if (! $agendamento) {
            notify::error(mensagem: 'Agendamento não encontrado para edição.');
            $this->closeEditAgendamentoModal();

            return;
        }

        $data = $this->editAgendamentoForm->getState();
        $servico = Servico::query()->find($data['servico_id']);
        $controlaPosicao = (bool) $servico?->controla_posicao;
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
            'updated_by' => Auth::id(),
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
            descricao: 'Agendamento editado pela tela mobile.',
            userId: Auth::id(),
        );

        notify::success(mensagem: 'Agendamento atualizado com sucesso!');
        $this->closeEditAgendamentoModal();
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

        notify::success(mensagem: $service->getMessage());
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

        notify::success(mensagem: $service->getMessage());
    }

    public function encerrarAgendamento(int $agendamentoId): void
    {
        $agendamento = Agendamento::query()->find($agendamentoId);

        if (! $agendamento) {
            notify::error(mensagem: 'Agendamento não encontrado.');

            return;
        }

        $service = new AgendamentoService;
        $service->encerrar($agendamento);

        if ($service->hasError()) {
            notify::error(mensagem: $service->getMessage());

            return;
        }

        notify::success(mensagem: $service->getMessage());
    }

    public function getAgendamentosProperty(): Collection
    {
        $query = $this->baseQuery();

        return match ($this->activeTab) {
            'atrasados' => $query->pendentes()->atrasados()->limit(40)->get(),
            'amanha' => $query->pendentes()->agendadosPara(now()->addDay()->toDateString())->limit(40)->get(),
            'sem-data' => $query->pendentes()->semData()->limit(40)->get(),
            'checklist' => $query->checklist()->abertos()->limit(40)->get(),
            default => $query->abertos()->agendadosPara(now()->toDateString())->limit(40)->get(),
        };
    }

    protected function baseQuery(): Builder
    {
        return Agendamento::query()
            ->with(['veiculo:id,placa', 'servico:id,descricao,controla_posicao', 'parceiro:id,nome'])
            ->when(filled($this->busca), function (Builder $query): void {
                $needle = '%'.str_replace(' ', '%', trim($this->busca)).'%';

                $query->where(function (Builder $subquery) use ($needle): void {
                    $subquery
                        ->where('observacao', 'like', $needle)
                        ->orWhereHas('veiculo', fn (Builder $query) => $query->where('placa', 'like', $needle))
                        ->orWhereHas('servico', fn (Builder $query) => $query->where('descricao', 'like', $needle))
                        ->orWhereHas('parceiro', fn (Builder $query) => $query->where('nome', 'like', $needle));
                });
            })
            ->orderByRaw('CASE WHEN data_agendamento IS NULL THEN 1 ELSE 0 END')
            ->orderBy('data_agendamento')
            ->orderBy('data_limite')
            ->orderBy('id');
    }

    public function getHojeCount(): int
    {
        return Agendamento::query()->abertos()->agendadosPara(now()->toDateString())->count();
    }

    public function getAtrasadosCount(): int
    {
        return Agendamento::query()->pendentes()->atrasados()->count();
    }

    public function getAmanhaCount(): int
    {
        return Agendamento::query()->pendentes()->agendadosPara(now()->addDay()->toDateString())->count();
    }

    public function getSemDataCount(): int
    {
        return Agendamento::query()->pendentes()->semData()->count();
    }

    public function getChecklistCount(): int
    {
        return Agendamento::query()->checklist()->abertos()->count();
    }

    public function getListUrl(): string
    {
        return AgendamentoResource::getUrl('index');
    }

    public function getOperacaoUrl(): string
    {
        return AgendamentoResource::getUrl('operacao');
    }

    public function formatCategoria(CategoriaAgendamentoEnum|string|null $categoria): string
    {
        return $categoria instanceof CategoriaAgendamentoEnum ? $categoria->value : (string) $categoria;
    }

    public function getStatusBadgeColor(Agendamento $agendamento): string
    {
        return match ($agendamento->status) {
            StatusOrdemServicoEnum::PENDENTE => 'warning',
            StatusOrdemServicoEnum::EXECUCAO => 'info',
            StatusOrdemServicoEnum::CONCLUIDO => 'success',
            StatusOrdemServicoEnum::CANCELADO => 'danger',
            default => 'gray',
        };
    }

    public function canVincular(Agendamento $agendamento): bool
    {
        return $agendamento->status === StatusOrdemServicoEnum::PENDENTE && $agendamento->ordem_servico_id === null;
    }
}
