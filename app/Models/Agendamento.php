<?php

namespace App\Models;

use App\Enum\Agendamento\CategoriaAgendamentoEnum;
use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Agendamento extends Model
{
    protected $casts = [
        'categoria' => CategoriaAgendamentoEnum::class,
        'status' => StatusOrdemServicoEnum::class,
        'data_agendamento' => 'date',
        'data_limite' => 'date',
        'data_realizado' => 'date',
    ];

    public function scopeDoVeiculo(Builder $query, int|array|null $veiculoId): Builder
    {
        if (is_null($veiculoId)) {
            return $query;
        }

        return is_array($veiculoId)
            ? $query->whereIn('veiculo_id', $veiculoId)
            : $query->where('veiculo_id', $veiculoId);
    }

    public function scopeAbertos(Builder $query): Builder
    {
        return $query->whereIn('status', [
            StatusOrdemServicoEnum::PENDENTE,
            StatusOrdemServicoEnum::EXECUCAO,
        ]);
    }

    public function scopePendentes(Builder $query): Builder
    {
        return $query->where('status', StatusOrdemServicoEnum::PENDENTE);
    }

    public function scopeEmExecucao(Builder $query): Builder
    {
        return $query->where('status', StatusOrdemServicoEnum::EXECUCAO);
    }

    public function scopeChecklist(Builder $query): Builder
    {
        return $query->where('categoria', CategoriaAgendamentoEnum::CHECKLIST);
    }

    public function scopeSemData(Builder $query): Builder
    {
        return $query->whereNull('data_agendamento');
    }

    public function scopeAgendadosPara(Builder $query, string $data): Builder
    {
        return $query->whereDate('data_agendamento', $data);
    }

    public function scopeEntreDatas(Builder $query, string $inicio, string $fim): Builder
    {
        return $query->whereBetween('data_agendamento', [$inicio, $fim]);
    }

    public function scopeAtrasados(Builder $query): Builder
    {
        return $query->whereDate('data_agendamento', '<', now()->toDateString());
    }

    public function scopeSemOrdemServico(Builder $query): Builder
    {
        return $query->whereNull('ordem_servico_id');
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class)->withDefault(
            ['ordem_servico_id' => 0]
        );
    }

    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class);
    }

    public function planoPreventivo(): BelongsTo
    {
        return $this->belongsTo(PlanoPreventivo::class);
    }

    public function parceiro(): BelongsTo
    {
        return $this->belongsTo(Parceiro::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
