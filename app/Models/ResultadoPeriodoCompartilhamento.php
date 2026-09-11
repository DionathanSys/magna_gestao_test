<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultadoPeriodoCompartilhamento extends Model
{
    protected $fillable = [
        'token_hash',
        'resultado_periodo_ids',
        'destinatario_nome',
        'destinatario_email',
        'criado_por_id',
        'expires_at',
        'last_accessed_at',
    ];

    protected $casts = [
        'resultado_periodo_ids' => 'array',
        'expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por_id');
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    public function isValid(): bool
    {
        return $this->expires_at?->isFuture() ?? false;
    }
}
