<?php

namespace App\Models;

use App\Events\Viagem\RecalcularRateioKmDispersaoRequested;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CargaViagem extends Model
{
    protected $table = 'cargas_viagem';

    protected static function booted(): void
    {
        static::created(function (self $model): void {
            RecalcularRateioKmDispersaoRequested::dispatch($model->viagem_id, 'carga_created');
        });

        static::deleted(function (self $model): void {
            RecalcularRateioKmDispersaoRequested::dispatch($model->viagem_id, 'carga_deleted');
        });
    }

    public function viagem(): BelongsTo
    {
        return $this->belongsTo(Viagem::class, 'viagem_id');
    }

    public function integrado(): BelongsTo
    {
        return $this->belongsTo(Integrado::class, 'integrado_id');
    }

}
