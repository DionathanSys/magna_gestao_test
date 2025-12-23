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
        public readonly array $destinos,
        public readonly string $veiculo,
        public readonly array $motorista = [],
        public readonly ?int $userId = null,
        public readonly ?string $observacao = null,
        public readonly array $nro_notas = [],
        public readonly bool $cte_retroativo = false,
        public readonly bool $cte_complementar = false,
        public readonly ?string $cte_referencia = null,
        public array $errors = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $quantidadeCte = count($data['destinos'] ?? []);
        $valorFreteUnitario = $quantidadeCte > 0 ? (float) ($data['valor_frete'] ?? 0) / $quantidadeCte : 0;

        return new self(
            kmTotal: (float) ($data['km_total'] ?? 0),
            valorFreteTotal: (float) ($data['valor_frete']),
            valorFreteUnitario: $valorFreteUnitario,
            quantidadeCte: (int) $quantidadeCte,
            anexos: $data['anexos'] ?? [],
            destinos: $data['destinos'] ?? [],
            veiculo: $data['veiculo'] ?? 'Não informado',
            motorista: $data['motorista'] ?? [],
            userId: $data['created_by'],
            observacao: $data['observacao'] ?? null,
            nro_notas: $data['nro_notas'] ?? [],
            cte_retroativo: $data['cte_retroativo'] ?? false,
            cte_complementar: $data['cte_complementar'] ?? false,
            cte_referencia: $data['cte_referencia'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'km_total'          => $this->kmTotal,
            'anexos'            => $this->anexos,
            'destinos'          => $this->destinos,
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
            'quantidade_integrados' => count($this->destinos),
            'integrados_ids' => array_column($this->destinos, 'integrado_id'),
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
