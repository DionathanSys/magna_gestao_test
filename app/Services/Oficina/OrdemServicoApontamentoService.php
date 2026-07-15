<?php

namespace App\Services\Oficina;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Models\Colaborador;
use App\Models\OrdemServico;
use App\Models\OrdemServicoApontamento;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrdemServicoApontamentoService
{
    public function iniciar(OrdemServico $ordemServico, string $codigoColaborador, Carbon|string $iniciadoEm): OrdemServicoApontamento
    {
        $colaborador = $this->resolveMecanico($codigoColaborador);
        $iniciadoEm = $this->validarHorario($iniciadoEm);

        return DB::transaction(function () use ($ordemServico, $colaborador, $iniciadoEm): OrdemServicoApontamento {
            $jaAberto = OrdemServicoApontamento::query()
                ->where('ordem_servico_id', $ordemServico->id)
                ->where('colaborador_id', $colaborador->id)
                ->whereNull('encerrado_em')
                ->exists();

            if ($jaAberto) {
                throw new InvalidArgumentException('Este colaborador já possui trabalho iniciado nesta OS.');
            }

            if ($ordemServico->status === StatusOrdemServicoEnum::PENDENTE) {
                $ordemServico->update(['status' => StatusOrdemServicoEnum::EXECUCAO]);
            }

            return OrdemServicoApontamento::create([
                'ordem_servico_id' => $ordemServico->id,
                'colaborador_id' => $colaborador->id,
                'iniciado_em' => $iniciadoEm,
            ]);
        });
    }

    public function encerrar(OrdemServico $ordemServico, string $codigoColaborador, Carbon|string $encerradoEm, array $itemIds): OrdemServicoApontamento
    {
        if ($itemIds === []) {
            throw new InvalidArgumentException('Selecione ao menos um serviço executado.');
        }

        $colaborador = $this->resolveMecanico($codigoColaborador);
        $encerradoEm = $this->validarHorario($encerradoEm);

        return DB::transaction(function () use ($ordemServico, $colaborador, $encerradoEm, $itemIds): OrdemServicoApontamento {
            $apontamento = OrdemServicoApontamento::query()
                ->where('ordem_servico_id', $ordemServico->id)
                ->where('colaborador_id', $colaborador->id)
                ->whereNull('encerrado_em')
                ->lockForUpdate()
                ->first();

            if (! $apontamento) {
                throw new InvalidArgumentException('Não há trabalho aberto para este colaborador nesta OS.');
            }

            if ($encerradoEm->lessThan($apontamento->iniciado_em)) {
                throw new InvalidArgumentException('A hora final não pode ser menor que a hora inicial.');
            }

            $itensValidos = $ordemServico->itens()
                ->whereIn('id', $itemIds)
                ->pluck('id')
                ->all();

            if (count($itensValidos) !== count(array_unique($itemIds))) {
                throw new InvalidArgumentException('A seleção possui serviços que não pertencem a esta OS.');
            }

            $apontamento->update(['encerrado_em' => $encerradoEm]);
            $apontamento->itens()->sync($itensValidos);

            return $apontamento->load(['colaborador', 'itens.servico']);
        });
    }

    private function resolveMecanico(string $codigo): Colaborador
    {
        $colaborador = Colaborador::query()
            ->where('codigo', trim($codigo))
            ->where('ativo', true)
            ->first();

        if (! $colaborador) {
            throw new InvalidArgumentException('Colaborador ativo não encontrado para o código informado.');
        }

        if ($colaborador->tipo !== 'MECANICO') {
            throw new InvalidArgumentException('O colaborador informado não está cadastrado como mecânico.');
        }

        return $colaborador;
    }

    private function validarHorario(Carbon|string $horario): Carbon
    {
        $horario = $horario instanceof Carbon ? $horario : Carbon::parse($horario);
        $agora = now();

        if ($horario->greaterThan($agora)) {
            throw new InvalidArgumentException('O horário não pode ser maior que a hora atual.');
        }

        if ($horario->lessThan($agora->copy()->subMinutes(5))) {
            throw new InvalidArgumentException('O horário só pode ser ajustado até 5 minutos para trás.');
        }

        return $horario;
    }
}
