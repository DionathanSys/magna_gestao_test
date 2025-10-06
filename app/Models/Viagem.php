<?php

namespace App\Models;

use App\Enum\MotivoDivergenciaViagem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Viagem extends Model
{
    protected $table = 'viagens';

    protected $casts = [
        'divergencias'          => 'array',
        'conferido'             => 'boolean',
        'motivo_divergencia'    => MotivoDivergenciaViagem::class,
    ];

    public function cargas(): HasMany
    {
        return $this->hasMany(CargaViagem::class, 'viagem_id');
    }

    public function carga(): HasOne
    {
        return $this->hasOne(CargaViagem::class, 'viagem_id');
    }

    public function integrados(): HasManyThrough
    {
        return $this->hasManyThrough(
            Integrado::class,
            CargaViagem::class,
            'viagem_id', // Foreign key on CargaViagem table
            'id', // Foreign key on Integrado table
            'id', // Local key on Viagem table
            'integrado_id' // Local key on CargaViagem table
        );
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoFrete::class, 'documento_transporte', 'documento_transporte');
    }

    public function complementos(): HasMany
    {
        return $this->hasMany(ViagemComplemento::class, 'viagem_id');
    }

    public function veiculo()
    {
        return $this->belongsTo(Veiculo::class, 'veiculo_id');
    }

    public function anotacoes(): HasMany
    {
        return $this->hasMany(AnotacaoViagem::class, 'viagem_id');
    }

    public function divergencias(): HasMany
    {
        return $this->hasMany(DivergenciaViagem::class, 'viagem_id');
    }

    public function comentarios(): MorphMany
    {
        return $this->morphMany(Comentario::class, 'comentavel');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function getIntegradosNomesAttribute(): string
    {
        return $this->cargas
            ->whereNotNull('integrado')
            ->map(fn($carga) => $carga->integrado->nome . ' - ' . $carga->integrado->municipio)
            ->unique()
            ->whenEmpty(fn() => collect(['Sem Integrado']))
            ->implode('<br>');
    }

    public function getIntegradosCodigosAttribute(): string
    {
        return $this->cargas
            ->whereNotNull('integrado')
            ->pluck('integrado.codigo')
            ->unique()
            ->whenEmpty(fn() => collect(['N/A']))
            ->implode(', ');
    }


}
