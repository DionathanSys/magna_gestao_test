<?php

namespace App\Services\Agendamento;

use App\Models\Agendamento;
use App\Models\AgendamentoHistorico;
use Illuminate\Support\Facades\Auth;

class AgendamentoHistoricoService
{
    public function registrar(Agendamento $agendamento, string $tipoEvento, ?string $descricao = null, array $dados = [], ?int $userId = null): AgendamentoHistorico
    {
        return $agendamento->historicos()->create([
            'tipo_evento' => $tipoEvento,
            'descricao' => $descricao,
            'dados' => $dados,
            'created_by' => $userId ?? Auth::id(),
        ]);
    }

    public function registrarAlteracoes(Agendamento $agendamento, string $tipoEvento, array $antes, array $depois, ?string $descricao = null, ?int $userId = null): ?AgendamentoHistorico
    {
        $alteracoes = [];

        foreach ($depois as $campo => $valorDepois) {
            $valorAntes = $antes[$campo] ?? null;

            if ($valorAntes === $valorDepois) {
                continue;
            }

            $alteracoes[$campo] = [
                'antes' => $valorAntes,
                'depois' => $valorDepois,
            ];
        }

        if ($alteracoes === []) {
            return null;
        }

        return $this->registrar(
            agendamento: $agendamento,
            tipoEvento: $tipoEvento,
            descricao: $descricao,
            dados: ['alteracoes' => $alteracoes],
            userId: $userId,
        );
    }
}
