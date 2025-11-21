<?php

namespace App\DTO;

use Illuminate\Support\Collection;
use App\Models\Integrado;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PayloadCteDTO
{
    public function __construct(
        public readonly float $kmTotal,
        public readonly float $valorFreteTotal,
        public readonly float $valorFreteUnitario,
        public readonly int $quantidadeCte = 1,
        public readonly array $anexos,
        public readonly Collection $integrados,
        public readonly string $veiculo,
        public readonly array $motorista = [],
        public readonly ?int $userId = null,
        public readonly ?string $observacao = null,
        public array $errors = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $quantidadeCte = count($data['integrados'] ?? []);
        $valorFreteUnitario = $quantidadeCte > 0 ? (float) ($data['valor_frete'] ?? 0) / $quantidadeCte : 0;

        return new self(
            kmTotal: (float) ($data['km_total'] ?? 0),
            valorFreteTotal: (float) ($data['valor_frete']),
            valorFreteUnitario: $valorFreteUnitario,
            quantidadeCte: (int) $quantidadeCte,
            anexos: $data['anexos'] ?? [],
            integrados: collect($data['integrados'] ?? [])->map(fn($item) =>
                new IntegradoCteDto(
                    integradoId: (int) $item['integrado_id'],
                    kmRota: (float) ($item['km_rota'] ?? 0)
                )
            ),
            veiculo: $data['veiculo'] ?? 'Não informado',
            motorista: $data['motorista'] ?? [],
            userId: Auth::id(),
            observacao: $data['observacao'] ?? null,
        );
    }

    public function validate(): array
    {
        // Validar KM total
        if ($this->kmTotal <= 0) {
            $this->errors[] = 'KM total deve ser maior que zero';
        }

        // Validar anexos - deve ter pelo menos um PDF e um XML
        if (empty($this->anexos)) {
            $this->errors[] = 'Pelo menos um anexo deve ser enviado';
        } else {
            $hasPdf = false;
            $hasXml = false;
            foreach ($this->anexos as $anexo) {
                $extension = strtolower(pathinfo($anexo->getClientOriginalName(), PATHINFO_EXTENSION));
                if ($extension === 'pdf') {
                    $hasPdf = true;
                }
                if ($extension === 'xml') {
                    $hasXml = true;
                }
            }

            if (!$hasPdf) {
                $this->errors[] = 'Pelo menos um arquivo PDF deve ser enviado';
            }

            if (!$hasXml) {
                $this->errors[] = 'Pelo menos um arquivo XML deve ser enviado';
            }
        }

        // Validar integrados
        if ($this->integrados->isEmpty()) {
            $this->errors[] = 'Pelo menos um integrado deve ser selecionado';
        } else {
            // Verificar se todos os integrados existem no banco
            //TODO: Verificar necessidade, pois poderia ser feito via IntegradoDTO
            $integradosIds = $this->integrados->pluck('integradoId')->toArray();
            $integradosExistentes = Integrado::whereIn('id', $integradosIds)->pluck('id')->toArray();
            $integradosInexistentes = array_diff($integradosIds, $integradosExistentes);

            if (!empty($integradosInexistentes)) {
                $this->errors[] = 'Os seguintes integrados não foram encontrados: ' . implode(', ', $integradosInexistentes);
            }

            // Validar KM de cada integrado
            foreach ($this->integrados as $index => $integrado) {
                if ($integrado->kmRota <= 0) {
                    $nomeIntegrado = $integrado->getNomeIntegrado();
                    $this->errors[] = "KM da rota do integrado '{$nomeIntegrado}' deve ser maior que zero";
                }
            }

            // Validar se a soma dos KMs dos integrados confere com o total
            $somakKmIntegrados = $this->integrados->sum('kmRota');
            if (abs($this->kmTotal - $somakKmIntegrados) > 0.01) { // Tolerância de 0.01 para problemas de float
                $this->errors[] = "A soma dos KMs dos integrados ({$somakKmIntegrados}) não confere com o KM total ({$this->kmTotal})";
            }
        }

        return $this->errors;
    }

    public function isValid(): bool
    {
        return empty($this->validate());
    }

    public function toArray(): array
    {
        return [
            'km_total'          => $this->kmTotal,
            'anexos'            => $this->anexos,
            'integrados'        => $this->integrados->toArray(),
            'veiculo'           => $this->veiculo,
            'motorista'         => $this->motorista,
            'user_id'           => $this->userId,
            'observacao'        => $this->observacao,
            'data_solicitacao'  => now()->toISOString(),
        ];
    }

    public function toLogData(): array
    {
        return [
            'km_total' => $this->kmTotal,
            'quantidade_anexos' => count($this->anexos),
            'tipos_anexos' => $this->getTiposAnexos(),
            'quantidade_integrados' => $this->integrados->count(),
            'integrados_ids' => $this->integrados->pluck('integradoId')->toArray(),
            'user_id' => $this->userId,
            'timestamp' => now()->toISOString(),
        ];
    }

    private function getTiposAnexos(): array
    {
        $tipos = [];
        foreach ($this->anexos as $anexo) {
            $extension = strtolower(pathinfo($anexo, PATHINFO_EXTENSION));
            $tipos[$extension] = ($tipos[$extension] ?? 0) + 1;
        }
        return $tipos;
    }
}
