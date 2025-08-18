<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recapagem extends Model
{
    protected $table = 'recapagens';

    public function pneu(): BelongsTo
    {
        return $this->belongsTo(Pneu::class, 'pneu_id');
    }

    public function desenhoPneu(): BelongsTo
    {
        return $this->belongsTo(DesenhoPneu::class, 'desenho_pneu_id');
    }

    public function parceiro(): BelongsTo
    {
        return $this->belongsTo(Parceiro::class, 'parceiro_id');
    }


}
