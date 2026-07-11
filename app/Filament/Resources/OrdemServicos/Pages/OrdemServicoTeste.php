<?php

namespace App\Filament\Resources\OrdemServicos\Pages;

use App\Filament\Resources\Agendamentos\AgendamentoResource;
use App\Filament\Resources\OrdemServicos\Actions;
use App\Filament\Resources\OrdemServicos\OrdemServicoResource;
use App\Models\Agendamento;
use App\Models\ManutencaoLancamento;
use App\Services\Agendamento\AgendamentoService;
use App\Services\Manutencao\ManutencaoLancamentoVinculoService;
use App\Services\NotificacaoService as notify;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Size;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class OrdemServicoTeste extends Page
{
    use InteractsWithRecord;

    protected static string $resource = OrdemServicoResource::class;

    protected static ?string $title = 'Ordem de Serviço';

    protected string $view = 'filament.resources.ordem-servicos.pages.ordem-servico-teste';

    public string $agendamentoBusca = '';

    public string $agendamentoFiltroCategoria = 'todos';

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
                Actions\VincularPlanoPreventivoAction::make($this->record->id, $this->record->veiculo_id)
                    ->size(Size::ExtraSmall),
                Actions\VincularOrdemSankhyaAction::make()
                    ->size(Size::ExtraSmall),
                Actions\EncerrarOrdemServicoAction::make()
                    ->size(Size::ExtraSmall)
                    ->successRedirectUrl(fn (Model $record): string => OrdemServicoResource::getUrl()),
                Actions\PdfOrdemServicoAction::make()
                    ->size(Size::ExtraSmall),
                DeleteAction::make('delete')
                    ->size(Size::ExtraSmall)
                    ->requiresConfirmation()
                    ->action(fn () => $this->record->delete()),
            ])->buttonGroup()->size(Size::ExtraSmall),
        ];
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

    public function getAgendamentosVeiculoProperty(): Collection
    {
        return $this->record->agendamentosPendentes
            ->filter(function (Agendamento $agendamento): bool {
                $matchCategoria = $this->agendamentoFiltroCategoria === 'todos'
                    || $agendamento->categoria?->value === $this->agendamentoFiltroCategoria;

                if (! $matchCategoria) {
                    return false;
                }

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

    public function getAgendamentoResumoProperty(): array
    {
        $agendamentos = $this->record->agendamentosPendentes;

        return [
            'total' => $agendamentos->count(),
            'atrasados' => $agendamentos->filter(fn (Agendamento $agendamento): bool => $agendamento->data_agendamento?->lt(today()) ?? false)->count(),
            'sem_data' => $agendamentos->whereNull('data_agendamento')->count(),
            'checklist' => $agendamentos->filter(fn (Agendamento $agendamento): bool => $agendamento->categoria?->value === 'CHECKLIST')->count(),
        ];
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

    protected function loadRecordRelations(Model $record): Model
    {
        return $record->load([
            'agendamentosPendentes.servico:id,descricao',
            'agendamentosPendentes.parceiro:id,nome',
            'planoPreventivoVinculado.planoPreventivo:id,descricao,intervalo',
            'manutencaoLancamentos',
        ]);
    }
}
