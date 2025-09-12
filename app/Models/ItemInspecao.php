<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemInspecao extends Model
{
    protected $table = 'item_inspecao';

    public function inspecao()
    {
        return $this->belongsTo(Inspecao::class);
    }

    public function item_inspecionado()
    {
        return $this->morphTo();
    }
}
