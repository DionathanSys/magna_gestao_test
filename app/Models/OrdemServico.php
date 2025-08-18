<?php

namespace App\Models;

use App\Enum\OrdemServico\StatusOrdemServicoEnum;
use App\Enum\OrdemServico\TipoManutencaoEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class OrdemServico extends Model
{
    protected $table = 'ordens_servico';

    protected $casts = [
        'tipo_manutencao' => TipoManutencaoEnum::class,
        'status' => StatusOrdemServicoEnum::class,
    ];

    public function parceiro(): BelongsTo
    {
        return $this->belongsTo(Parceiro::class, 'parceiro_id');
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class, 'veiculo_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemOrdemServico::class, 'ordem_servico_id');
    }

    public function servicos(): HasManyThrough
    {
        return $this->hasManyThrough(
            Servico::class,
            ItemOrdemServico::class,
            'ordem_servico_id',
            'id',
            'id',
            'servico_id'
        );
    }

    public function sankhyaId(): HasMany
    {
        return $this->hasMany(OrdemSankhya::class, 'ordem_servico_id');
    }

    public function comentarios(): MorphMany
    {
        return $this->morphMany(Comentario::class, 'comentavel');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function agendamentos(): HasMany
    {
        return $this->hasMany(Agendamento::class, 'ordem_servico_id');
    }

    public function agendamentosPendentes(): HasMany
    {
        return $this->hasMany(Agendamento::class, 'veiculo_id', 'veiculo_id')
            ->where('status', StatusOrdemServicoEnum::PENDENTE);
    }

    public function pendentes(): HasMany
    {
        return $this->hasMany(Agendamento::class, 'veiculo_id', 'veiculo_id')
            ->where('status', StatusOrdemServicoEnum::PENDENTE);
    }

    public function planoPreventivoVinculado(): HasMany
    {
        return $this->hasMany(PlanoManutencaoOrdemServico::class, 'ordem_servico_id');
    }
}
