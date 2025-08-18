<?php

namespace App\Models;

use App\Enum\Viagem\StatusViagemEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViagemComplemento extends Model
{
    protected $casts = [
        'conferido' => 'boolean',
        'status' => StatusViagemEnum::class,
    ];

    public function viagem(): BelongsTo
    {
        return $this->belongsTo(Viagem::class);
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function integrado(): BelongsTo
    {
        return $this->belongsTo(Integrado::class,);
    }

}
