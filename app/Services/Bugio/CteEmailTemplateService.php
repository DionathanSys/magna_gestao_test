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
        return trim(strip_tags($this->replacePlaceholders($this->subjectTemplate(), $payload, false)));
    }

    public function renderBody(PayloadCteDTO $payload): string
    {
        return $this->replacePlaceholders($this->bodyTemplate(), $payload, true);
    }

    protected function replacePlaceholders(string $template, PayloadCteDTO $payload, bool $htmlMode): string
    {
        $escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $stringOrNA = function (mixed $value) use ($escape): string {
            $value = trim((string) ($value ?? ''));

            return $value !== '' ? $escape($value) : 'N/A';
        };
        $stringOrEmpty = function (mixed $value) use ($escape): string {
            $value = trim((string) ($value ?? ''));

            return $value !== '' ? $escape($value) : '';
        };

        $destinatarios = collect($payload->destinos)
            ->map(fn (array $destino) => $stringOrNA($destino['integrado_nome'] ?? null))
            ->values();

        $destinatariosFormatted = $htmlMode
            ? $destinatarios->implode('<br>')
            : $destinatarios->map(fn (string $destino) => '- ' . $destino)->implode("\n");

        $linhaCteRetroativo = $payload->cte_retroativo
            ? ($htmlMode ? '<p><strong>CTe Retroativo</strong></p>' : 'CTe Retroativo')
            : '';

        $linhaCteComplementar = $payload->cte_complementar
            ? ($htmlMode
                ? '<p><strong>Complementar ao CT-e: ' . $stringOrNA($payload->cte_referencia) . '</strong></p>'
                : 'Complementar ao CT-e: ' . $stringOrNA($payload->cte_referencia))
            : '';

        $linhaAltoDesempenho = (! $payload->cte_retroativo && ! $payload->cte_complementar)
            ? ($htmlMode ? '<p><strong>Marcar MDF-e como "Alto Desempenho"</strong></p>' : 'Marcar MDF-e como "Alto Desempenho"')
            : '';

        return strtr($template, [
            '{placa}' => $stringOrNA($payload->veiculo),
            '{notas}' => $payload->nro_notas !== [] ? $escape(implode(', ', $payload->nro_notas)) : 'N/A',
            '{agora}' => $escape(now()->format('d/m/Y H:i')),
            '{valor_frete_total}' => $escape(number_format($payload->valorFreteTotal, 2, ',', '.')),
            '{quantidade_cte}' => $escape((string) $payload->quantidadeCte),
            '{valor_frete_unitario}' => $escape(number_format($payload->valorFreteUnitario, 2, ',', '.')),
            '{motorista_nome}' => $stringOrNA($payload->motorista['nome'] ?? null),
            '{motorista_cpf}' => $stringOrNA($payload->motorista['cpf'] ?? null),
            '{destinatarios}' => $destinatariosFormatted,
            '{cte_referencia}' => $stringOrNA($payload->cte_referencia),
            '{linha_cte_retroativo}' => $linhaCteRetroativo,
            '{linha_cte_complementar}' => $linhaCteComplementar,
            '{linha_alto_desempenho}' => $linhaAltoDesempenho,
            '{peso_carga}' => $payload->pesoCarga !== null ? $escape(number_format($payload->pesoCarga, 3, ',', '.') . ' kg') : 'N/A',
            '{data_competencia}' => $stringOrNA($payload->dataCompetencia),
            '{observacao}' => $stringOrEmpty($payload->observacao),
        ]);
    }
}
