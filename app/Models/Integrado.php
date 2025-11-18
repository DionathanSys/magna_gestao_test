<?php

namespace App\Models;

use App\Services;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Log;

class Integrado extends Model
{
    protected $casts = [
        'cliente' => \App\Enum\ClienteEnum::class,
        'alerta_viagem' => 'boolean',
    ];

    public function cargas(): HasMany
    {
        return $this->hasMany(CargaViagem::class, 'integrado_id');
    }

    public function comentarios(): MorphMany
    {
        return $this->morphMany(Comentario::class, 'comentavel');
    }

    protected static function booted()
    {
        static::updated(function (self $model) {
            if ($model->isDirty('alerta_viagem')) {
                Services\Integrado\IntegradoService::invalidarCacheIntegradosAlerta();
                
                Log::info('Cache de alertas invalidado por atualização', [
                    'integrado_id' => $model->id,
                    'alerta_viagem' => $model->alerta_viagem,
                ]);
            }
        });

        static::created(function (self $model) {
            if ($model->alerta_viagem) {
                Services\Integrado\IntegradoService::invalidarCacheIntegradosAlerta();
                
                Log::info('Cache de alertas invalidado por criação', [
                    'integrado_id' => $model->id,
                ]);
            }
        });

        static::deleted(function (self $model) {
            if ($model->alerta_viagem) {
                Services\Integrado\IntegradoService::invalidarCacheIntegradosAlerta();
                
                Log::info('Cache de alertas invalidado por exclusão', [
                    'integrado_id'  => $model->id,
                    'integrado'     => $model->nome,
                ]);
            }
        });
    }

}
