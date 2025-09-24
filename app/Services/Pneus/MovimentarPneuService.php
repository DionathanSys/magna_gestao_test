<?php

namespace App\Services\Pneus;

use App\Enum\Pneu\LocalPneuEnum;
use App\Enum\Pneu\MotivoMovimentoPneuEnum;
use App\Enum\Pneu\StatusPneuEnum;
use App\Models\HistoricoMovimentoPneu;
use App\Models\PneuPosicaoVeiculo;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class MovimentarPneuService
{

    protected HistoricoMovimentoPneu $historicoMovimentoPneu;

    public function __construct()
    {
        $this->historicoMovimentoPneu = new HistoricoMovimentoPneu();
    }

    public function inverterPneu(PneuPosicaoVeiculo $pneuVeiculo, array $data)
    {
        $this->removerPneu($pneuVeiculo, [
            'data_final' => $data['data_movimento'],
            'km_final'   => $data['km_movimento'],
            'sulco'      => $data['sulco'] ?? 0,
            'motivo'     => $data['motivo'],
            'observacao' => $data['observacao'] ?? null,
            'anexos'     => $data['anexos'] ?? null,
        ]);

        $this->aplicarPneu($pneuVeiculo, [
            'pneu_id'       => $pneuVeiculo->pneu_id,
            'data_inicial'  => $data['data_movimento'],
            'km_inicial'    => $data['km_movimento'],
        ]);
    }


    public function removerPneu(PneuPosicaoVeiculo $pneuVeiculo, array $data)
    {

        if ($pneuVeiculo->km_inicial > $data['km_final']) {
            throw new \Exception('A KM final não pode ser menor que a KM inicial.');
        }

        //TODO: Separar em outro serviço a criação do histórico
        $this->historicoMovimentoPneu->create([
            'pneu_id'           => $pneuVeiculo->pneu_id,
            'veiculo_id'        => $pneuVeiculo->veiculo_id,
            'data_inicial'      => $pneuVeiculo->data_inicial,
            'km_inicial'        => $pneuVeiculo->km_inicial,
            'eixo'              => $pneuVeiculo->eixo,
            'posicao'           => $pneuVeiculo->posicao,
            'motivo'            => $data['motivo'],
            'sulco_movimento'   => $data['sulco'],
            'data_final'        => $data['data_final'],
            'km_final'          => $data['km_final'],
            'ciclo_vida'        => $pneuVeiculo->pneu->ciclo_vida,
            'observacao'        => $data['observacao'],
            'anexos'            => $data['anexos'] ?? null,
        ]);

        $pneuVeiculo->update([
            'pneu_id'       => null,
            'data_inicial'  => null,
            'km_inicial'    => null,
        ]);

        switch ($data['motivo']) {
            case MotivoMovimentoPneuEnum::CONSERTO->value:
                $pneuVeiculo->pneu->update([
                    'status' => StatusPneuEnum::INDISPONIVEL,
                    'local'  => LocalPneuEnum::MANUTENCAO,
                ]);
                break;
            case MotivoMovimentoPneuEnum::RECAPAGEM->value:
                $pneuVeiculo->pneu->update([
                    'status' => StatusPneuEnum::INDISPONIVEL,
                    'local'  => LocalPneuEnum::ESTOQUE_CTV,
                ]);
                break;

            case MotivoMovimentoPneuEnum::ESTEPE->value:
                $pneuVeiculo->pneu->update([
                    'status' => StatusPneuEnum::DISPONIVEL,
                    'local'  => LocalPneuEnum::ESTOQUE_CCO,
                ]);
                break;
            case MotivoMovimentoPneuEnum::SUCATEAR->value:
                $pneuVeiculo->pneu->update([
                    'status' => StatusPneuEnum::SUCATA,
                    'local'  => LocalPneuEnum::SUCATA,
                ]);
                break;
        }
    }

    public function aplicarPneu(PneuPosicaoVeiculo $pneuVeiculo, array $data)
    {
        $pneuVeiculo->update([
            'pneu_id'       => $data['pneu_id'],
            'data_inicial'  => $data['data_inicial'],
            'km_inicial'    => $data['km_inicial'],
        ]);

        $pneuVeiculo->pneu()->update([
            'status' => StatusPneuEnum::EM_USO,
            'local'  => LocalPneuEnum::FROTA,
        ]);

    }

    public function trocarPneu(PneuPosicaoVeiculo $pneuVeiculo, array $data)
    {

        $this->removerPneu($pneuVeiculo, [
            'data_final' => $data['data_inicial'],
            'km_final'   => $data['km_inicial'],
            'sulco'      => $data['sulco'] ?? 0,
            'motivo'     => $data['motivo'],
            'observacao' => $data['observacao'] ?? null,
            'anexos'     => $data['anexos'] ?? null,
        ]);

        $this->aplicarPneu($pneuVeiculo, [
            'pneu_id'       => $data['pneu_id'],
            'data_inicial'  => $data['data_inicial'],
            'km_inicial'    => $data['km_inicial'],
        ]);
    }

    public function rodizioPneu(Collection $pneusVeiculo, array $data)
    {
        if ($pneusVeiculo->count() !== 2) {
            return;
        }

        $pneusId = $pneusVeiculo->pluck('pneu_id')->toArray();

        $pneusVeiculo->each(function (PneuPosicaoVeiculo $pneuVeiculo) use ($pneusId, $data) {

            $pneuId = Arr::where($pneusId, function ($id) use ($pneuVeiculo) {
                return $id !== $pneuVeiculo->pneu_id;
            });

            $pneuId = Arr::first($pneuId);

            $this->removerPneu($pneuVeiculo, [
                'data_final' => $data['data_movimento'],
                'km_final'   => $data['km_movimento'],
                'sulco'      => $data['sulco'] ?? 0,
                'motivo'     => MotivoMovimentoPneuEnum::RODIZIO->value,
                'observacao' => $data['observacao'] ?? null,
                'anexos'     => $data['anexos'] ?? null,
            ]);

            $this->aplicarPneu($pneuVeiculo, [
                'pneu_id'       => $pneuId,
                'data_inicial'  => $data['data_movimento'],
                'km_inicial'    => $data['km_movimento'],
            ]);
        });
    }
}
