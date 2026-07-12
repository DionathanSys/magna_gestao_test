<?php

namespace App\Filament\Resources\Agendamentos\Pages;

use App\Enum\Agendamento\CategoriaAgendamentoEnum;
use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Filament\Resources\Agendamentos\AgendamentoResource;
use App\Filament\Resources\Agendamentos\Schemas\AgendamentoForm;
use App\Models\Agendamento;
use App\Services\Agendamento\AgendamentoHistoricoService;
use App\Services\Agendamento\AgendamentoService;
use App\Services\NotificacaoService as notify;
use Filament\Actions\Action;
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

class OperacaoAgendamentos extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string $resource = AgendamentoResource::class;

    protected static ?string $title = 'Operação de Agendamentos';

    protected string $view = 'filament.resources.agendamentos.pages.operacao-agendamentos';

    public string $busca = '';

    public bool $showReprogramarModal = false;

    public ?int $editingAgendamentoId = null;

    public ?array $reprogramarData = [];

    public bool $showCreateAgendamentoModal = false;

    public ?array $createAgendamentoData = [];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('novo')
                ->label('Novo Agendamento')
                ->icon('heroicon-o-plus')
                ->action(fn () => $this->openCreateAgendamentoModal()),
            Action::make('lista')
                ->label('Abrir Lista Completa')
                ->url(AgendamentoResource::getUrl('index')),
            Action::make('mobile')
                ->label('Abrir Mobile')
                ->icon('heroicon-o-device-phone-mobile')
                ->url(AgendamentoResource::getUrl('mobile-operacao')),
        ];
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
            descricao: 'Agendamento reprogramado pela tela operacional.',
            userId: Auth::id(),
        );

        notify::success(mensagem: 'Agendamento reprogramado com sucesso!');
        $this->closeReprogramarModal();
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

    public function getAtrasadosProperty(): Collection
    {
        return $this->baseQuery()
            ->pendentes()
            ->atrasados()
            ->limit(25)
            ->get();
    }

    public function getHojeProperty(): Collection
    {
        return $this->baseQuery()
            ->abertos()
            ->agendadosPara(now()->toDateString())
            ->limit(25)
            ->get();
    }

    public function getAmanhaProperty(): Collection
    {
        return $this->baseQuery()
            ->pendentes()
            ->agendadosPara(now()->addDay()->toDateString())
            ->limit(25)
            ->get();
    }

    public function getSemDataProperty(): Collection
    {
        return $this->baseQuery()
            ->pendentes()
            ->semData()
            ->limit(25)
            ->get();
    }

    public function getChecklistProperty(): Collection
    {
        return $this->baseQuery()
            ->checklist()
            ->abertos()
            ->limit(25)
            ->get();
    }

    public function getAlemDeAmanhaProperty(): Collection
    {
        return $this->baseQuery()
            ->pendentes()
            ->whereDate('data_agendamento', '>', now()->addDay()->toDateString())
            ->limit(25)
            ->get();
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

    public function getResumoProperty(): array
    {
        return [
            'atrasados' => Agendamento::query()->pendentes()->atrasados()->count(),
            'hoje' => Agendamento::query()->abertos()->agendadosPara(now()->toDateString())->count(),
            'amanha' => Agendamento::query()->pendentes()->agendadosPara(now()->addDay()->toDateString())->count(),
            'sem_data' => Agendamento::query()->pendentes()->semData()->count(),
            'checklist' => Agendamento::query()->checklist()->abertos()->count(),
            'alem_de_amanha' => Agendamento::query()->pendentes()->whereDate('data_agendamento', '>', now()->addDay()->toDateString())->count(),
        ];
    }

    public function formatCategoria(CategoriaAgendamentoEnum|string|null $categoria): string
    {
        return $categoria instanceof CategoriaAgendamentoEnum ? $categoria->value : (string) $categoria;
    }

    public function canVincular(Agendamento $agendamento): bool
    {
        return $agendamento->status === StatusOrdemServicoEnum::PENDENTE && $agendamento->ordem_servico_id === null;
    }
}
