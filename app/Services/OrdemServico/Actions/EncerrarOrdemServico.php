<?php

namespace App\Services\OrdemServico\Actions;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Models;
use App\Services\Agendamento\Actions\EncerrarAgendamento;
use Illuminate\Support\Facades\DB;

class EncerrarOrdemServico
{
    public function __construct(
        protected Models\OrdemServico $ordemServico,
        protected bool $encerrarSankhya = false,
    ) {}

    public function handle(): Models\OrdemServico
    {
        return DB::transaction(function (): Models\OrdemServico {
            $this->ordemServico = Models\OrdemServico::query()
                ->whereKey($this->ordemServico->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ordemServico->loadMissing(['itens', 'agendamentos']);
            $this->validate();

            foreach ($this->ordemServico->itens as $item) {
                if ($item->status !== StatusOrdemServicoEnum::PENDENTE) {
                    continue;
                }

                if (! $item->update(['status' => StatusOrdemServicoEnum::CONCLUIDO])) {
                    throw new \RuntimeException("Erro ao encerrar o item {$item->id} da ordem de serviço.");
                }
            }

            foreach ($this->ordemServico->agendamentos as $agendamento) {
                if ($agendamento->status !== StatusOrdemServicoEnum::EXECUCAO) {
                    continue;
                }

                (new EncerrarAgendamento($agendamento))->handle();
            }

            $data = [
                'status' => StatusOrdemServicoEnum::CONCLUIDO,
                'data_fim' => now(),
            ];

            if ($this->encerrarSankhya) {
                $data['status_sankhya'] = StatusOrdemServicoEnum::CONCLUIDO;
            }

            if (! $this->ordemServico->update($data)) {
                throw new \RuntimeException("Erro ao encerrar a ordem de serviço {$this->ordemServico->id}.");
            }

            return $this->ordemServico->fresh(['itens', 'agendamentos']);
        });
    }

    public function validate(): void
    {
        if (! in_array($this->ordemServico->status, [StatusOrdemServicoEnum::PENDENTE, StatusOrdemServicoEnum::EXECUCAO])) {
            throw new \InvalidArgumentException('A ordem de serviço não pode ser encerrada no status atual: '.$this->ordemServico->status->value);
        }

        if ($this->ordemServico->itens->isEmpty()) {
            throw new \InvalidArgumentException('A ordem de serviço não pode ser encerrada sem itens.');
        }

        $apontamentosAbertos = $this->ordemServico->apontamentosAbertosOficina()
            ->with('colaborador')
            ->lockForUpdate()
            ->get();

        if ($apontamentosAbertos->isNotEmpty()) {
            $responsaveis = $apontamentosAbertos
                ->map(fn (Models\OrdemServicoApontamento $apontamento): string => $apontamento->colaborador?->nome ?? 'Responsável não informado')
                ->unique()
                ->join(', ');

            throw new \InvalidArgumentException('A ordem de serviço não pode ser encerrada com apontamentos em aberto. Responsável(is): '.$responsaveis.'.');
        }
    }
}
