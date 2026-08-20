<?php

namespace App\Services\ResultadoPeriodo;

use App\Enum\StatusDiversosEnum;
use App\Models\ResultadoPeriodo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use RuntimeException;

class ResultadoPeriodoVinculoService
{
    public function vincular(Model $registro, string $estrategia, ?string $dataReferencia = null, ?int $resultadoPeriodoId = null): bool
    {
        $this->validarRegistro($registro);

        $resultadoPeriodo = match ($estrategia) {
            'aberto' => $this->resultadoEmAberto($registro),
            'data_registro' => $this->resultadoPorData($registro, $this->dataDoRegistro($registro)),
            'data_informada' => $this->resultadoPorData($registro, $dataReferencia),
            'resultado_especifico' => $this->resultadoEspecifico($resultadoPeriodoId),
            default => throw new RuntimeException('Estratégia de vínculo inválida.'),
        };

        if (! $resultadoPeriodo) {
            throw new RuntimeException('Nenhum resultado período compatível foi encontrado para este veículo.');
        }

        if ((int) $resultadoPeriodo->veiculo_id !== (int) $registro->veiculo_id) {
            throw new RuntimeException('O resultado selecionado pertence a outro veículo.');
        }

        $this->validarPeriodoEditavel($resultadoPeriodo);

        if ((int) $registro->resultado_periodo_id === (int) $resultadoPeriodo->id) {
            return false;
        }

        if ($registro->resultado_periodo_id) {
            $this->validarPeriodoEditavel(ResultadoPeriodo::query()->findOrFail($registro->resultado_periodo_id));
        }

        $registro->update(['resultado_periodo_id' => $resultadoPeriodo->id]);

        return true;
    }

    public function desvincular(Model $registro): bool
    {
        $this->validarRegistro($registro);

        if (! $registro->resultado_periodo_id) {
            return false;
        }

        $this->validarPeriodoEditavel(ResultadoPeriodo::query()->findOrFail($registro->resultado_periodo_id));
        $registro->update(['resultado_periodo_id' => null]);

        return true;
    }

    private function resultadoEmAberto(Model $registro): ?ResultadoPeriodo
    {
        return ResultadoPeriodo::query()
            ->where('veiculo_id', $registro->veiculo_id)
            ->where('status', StatusDiversosEnum::PENDENTE->value)
            ->orderByDesc('data_fim')
            ->orderByDesc('id')
            ->first();
    }

    private function resultadoPorData(Model $registro, ?string $data): ?ResultadoPeriodo
    {
        if (! $data) {
            throw new RuntimeException('A data de referência é obrigatória para esta estratégia.');
        }

        return ResultadoPeriodo::query()
            ->where('veiculo_id', $registro->veiculo_id)
            ->where('status', StatusDiversosEnum::PENDENTE->value)
            ->whereDate('data_inicio', '<=', Carbon::parse($data)->toDateString())
            ->whereDate('data_fim', '>=', Carbon::parse($data)->toDateString())
            ->orderByDesc('data_fim')
            ->orderByDesc('id')
            ->first();
    }

    private function resultadoEspecifico(?int $resultadoPeriodoId): ?ResultadoPeriodo
    {
        if (! $resultadoPeriodoId) {
            throw new RuntimeException('Selecione o resultado período de destino.');
        }

        return ResultadoPeriodo::query()->find($resultadoPeriodoId);
    }

    private function dataDoRegistro(Model $registro): ?string
    {
        foreach (['data_competencia', 'data_emissao', 'data_abastecimento', 'data_negociacao'] as $atributo) {
            if (filled($registro->getAttribute($atributo))) {
                return (string) $registro->getAttribute($atributo);
            }
        }

        return null;
    }

    private function validarRegistro(Model $registro): void
    {
        if (! filled($registro->veiculo_id)) {
            throw new RuntimeException('O registro não possui veículo vinculado.');
        }

        if (! method_exists($registro, 'resultadoPeriodo')) {
            throw new RuntimeException('Este tipo de registro não pode ser vinculado a um resultado período.');
        }
    }

    private function validarPeriodoEditavel(ResultadoPeriodo $resultadoPeriodo): void
    {
        if ($resultadoPeriodo->status !== StatusDiversosEnum::PENDENTE->value) {
            throw new RuntimeException('Períodos encerrados não podem ter registros alterados. Reabra o período antes de continuar.');
        }
    }
}
