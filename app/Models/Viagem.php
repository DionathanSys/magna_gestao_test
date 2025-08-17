<?php

namespace App\Models;

use App\Enum\MotivoDivergenciaViagem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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


}
