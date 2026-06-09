<?php

namespace App\Services\Bugio;

use App\DTO\PayloadCteDTO;

class CteEmailTemplateService
{
    public function subjectTemplate(): string
    {
        return (string) db_config('config-bugio.email-assunto-cte', 'Solicitação CT-e Magnabosco - Bugio {placa} - {notas} - {agora}');
    }

    public function bodyTemplate(): string
    {
        return (string) db_config('config-bugio.email-corpo-cte', "Solicitamos a emissão de CT-e para a placa {placa} referente às NF's ({notas}) em anexo.\n\n{linha_cte_retroativo}{linha_cte_complementar}{linha_alto_desempenho}Valor total frete R$ {valor_frete_total}, sendo {quantidade_cte} CT-e(s), R$ {valor_frete_unitario} cada CT-e.\nPeso da carga: {peso_carga}\nData competência: {data_competencia}\nCNPJ Transportadora: 75.813.923/0010-52\nMotorista: {motorista_nome}\nCPF: {motorista_cpf}\n\nObservações:\nInício no município de Chapecó para PF: ICMS DIFERIDO, CST 051 (campo de observações: ICMS DIFERIDO CFME ARTIGO 122, INCISO II, ANEXO 6, DO RICMS/SC)\nRemetente: Bugio Nutrição – 50.593.076/0001-46\nDestinatário(s):\n{destinatarios}\nTomador: Bugio Agropecuária – 82.996.521/0001-05\n\nFavor responder este e-mail, incluindo todos os destinatários em cópia.\nObrigado!\nAxionSoft - Gestão");
    }

    public function renderSubject(PayloadCteDTO $payload): string
    {
        return $this->replacePlaceholders($this->subjectTemplate(), $payload);
    }

    public function renderBody(PayloadCteDTO $payload): string
    {
        return $this->replacePlaceholders($this->bodyTemplate(), $payload);
    }

    protected function replacePlaceholders(string $template, PayloadCteDTO $payload): string
    {
        $destinatarios = collect($payload->destinos)
            ->map(fn (array $destino) => '- ' . ($destino['integrado_nome'] ?? 'N/A'))
            ->implode("\n");

        return strtr($template, [
            '{placa}' => $payload->veiculo,
            '{notas}' => implode(', ', $payload->nro_notas ?? []),
            '{agora}' => now()->format('d/m/Y H:i'),
            '{valor_frete_total}' => number_format($payload->valorFreteTotal, 2, ',', '.'),
            '{quantidade_cte}' => (string) $payload->quantidadeCte,
            '{valor_frete_unitario}' => number_format($payload->valorFreteUnitario, 2, ',', '.'),
            '{motorista_nome}' => (string) ($payload->motorista['nome'] ?? 'Não informado'),
            '{motorista_cpf}' => (string) ($payload->motorista['cpf'] ?? 'Não informado'),
            '{destinatarios}' => $destinatarios,
            '{cte_referencia}' => (string) ($payload->cte_referencia ?? ''),
            '{linha_cte_retroativo}' => $payload->cte_retroativo ? "CTe Retroativo\n\n" : '',
            '{linha_cte_complementar}' => $payload->cte_complementar ? 'Complementar ao CT-e: ' . ($payload->cte_referencia ?? '-') . "\n\n" : '',
            '{linha_alto_desempenho}' => (! $payload->cte_retroativo && ! $payload->cte_complementar) ? "Marcar MDF-e como \"Alto Desempenho\"\n\n" : '',
            '{peso_carga}' => $payload->pesoCarga !== null ? number_format($payload->pesoCarga, 3, ',', '.') . ' kg' : 'Não informado',
            '{data_competencia}' => $payload->dataCompetencia ?: 'Não informado',
        ]);
    }
}
