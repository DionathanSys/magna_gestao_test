<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JustificativaDispersaoViagem extends Model
{
    protected $table = 'justificativas_dispersao_viagens';

    protected $casts = [
        'data_competencia' => 'date',
        'km_rodado' => 'decimal:2',
        'km_pago' => 'decimal:2',
        'km_dispersao' => 'decimal:2',
        'dispersao_percentual' => 'decimal:2',
    ];

    public function viagem(): BelongsTo
    {
        return $this->belongsTo(Viagem::class);
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
