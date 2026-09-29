<?php

namespace App\Services\Telegram;

use App\Models\Agendamento;
use App\Models\OrdemServico;
use App\Services\OrdemServico\OrdemServicoAlertaDataService;
use Illuminate\Support\Collection;

class OrdemServicoTelegramMessageService
{
    public function __construct(
        protected OrdemServicoAlertaDataService $dataService,
    ) {}

    public function make(OrdemServico $ordemServico): string
    {
        $ordemServico->loadMissing([
            'veiculo:id,placa',
            'parceiro:id,nome',
        ]);

        $agendamentos = $this->dataService
            ->agendamentosPendentes($ordemServico)
            ->sortBy(fn (Agendamento $agendamento): string => sprintf(
                '%s-%s-%010d',
                optional($agendamento->data_agendamento)->format('Ymd') ?? '99999999',
                optional($agendamento->data_limite)->format('Ymd') ?? '99999999',
                $agendamento->id,
            ))
            ->values();

        $planos = $this->dataService->planosPreventivos($ordemServico);

        $mensagem = [
            'Nova Ordem de Serviço criada',
            '',
            'OS: #'.$ordemServico->id,
            'Veículo: '.($ordemServico->veiculo?->placa ?? 'Não informado'),
            'Parceiro: '.($ordemServico->parceiro?->nome ?? 'Serviço interno'),
            'Tipo: '.($ordemServico->tipo_manutencao?->value ?? 'Não informado'),
            'Quilometragem: '.$this->number($ordemServico->quilometragem),
            'Abertura: '.(optional($ordemServico->data_inicio)->format('d/m/Y H:i') ?? 'Não informada'),
            '',
            'AGENDAMENTOS EM ABERTO',
            ...$this->formatAgendamentos($agendamentos),
            '',
            'PLANOS PREVENTIVOS (até '.
                $this->numberValue(OrdemServicoAlertaDataService::KM_BASE_RELATORIO_MANUTENCAO).
                ' km restantes)',
            ...$this->formatPlanos($planos),
        ];

        return implode("\n", $mensagem);
    }

    /**
     * @param  Collection<int, Agendamento>  $agendamentos
     * @return list<string>
     */
    private function formatAgendamentos(Collection $agendamentos): array
    {
        if ($agendamentos->isEmpty()) {
            return ['Nenhum agendamento pendente.'];
        }

        return $agendamentos
            ->values()
            ->map(function (Agendamento $agendamento, int $index): string {
                $servico = $agendamento->servico?->descricao ?? 'Serviço não informado';
                $categoria = $agendamento->categoria?->value ?? 'MANUAL';
                $data = optional($agendamento->data_agendamento)->format('d/m/Y') ?? 'Sem data';
                $limite = optional($agendamento->data_limite)->format('d/m/Y') ?? 'Sem data';
                $parceiro = $agendamento->parceiro?->nome ?? 'Serviço interno';

                $linha = ($index + 1).'. '.$servico.' ['.$categoria.']';
                $linha .= ' | Data: '.$data;
                $linha .= ' | Limite: '.$limite;
                $linha .= ' | Fornecedor: '.$parceiro;

                if (filled($agendamento->observacao)) {
                    $linha .= ' | Obs.: '.$agendamento->observacao;
                }

                return $linha;
            })
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $planos
     * @return list<string>
     */
    private function formatPlanos(Collection $planos): array
    {
        if ($planos->isEmpty()) {
            return ['Nenhum plano preventivo dentro da base de quilometragem.'];
        }

        return $planos
            ->values()
            ->map(function (array $plano, int $index): string {
                $dataPrevista = $plano['data_prevista'] ?? null;
                $dataPrevista = $dataPrevista === 'Atrasado'
                    ? 'Atrasado'
                    : ($dataPrevista ? date('d/m/Y', strtotime((string) $dataPrevista)) : '-');

                return ($index + 1).'. '.($plano['plano_descricao'] ?? 'Plano não informado')
                    .' | KM restante: '.$this->number($plano['km_restante'] ?? 0)
                    .' | KM atual: '.$this->number($plano['km_atual'] ?? 0)
                    .' | Próxima execução: '.$this->number($plano['proxima_execucao'] ?? 0)
                    .' | Data prevista: '.$dataPrevista;
            })
            ->all();
    }

    private function number(mixed $value): string
    {
        return $this->numberValue($value).' km';
    }

    private function numberValue(mixed $value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }
}
