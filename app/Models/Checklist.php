<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Checklist extends Model
{
    protected $casts = [
        'itens_verificados' => 'array',
        'itens_corrigidos'  => 'array',
        'pendencias'        => 'array',
        'anexos'            => 'array',
        'active'            => 'boolean',
    ];

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pendenciasCount(): Attribute
    {
        return Attribute::make(
            get: fn () => is_array($this->pendencias) ? count($this->pendencias) : 0,
        );
    }

    public function itensCorrigidosCount(): Attribute
    {
        return Attribute::make(
            get: fn () => is_array($this->itens_corrigidos) ? count($this->itens_corrigidos) : 0,
        );
    }

    public function itensVerificadosCount(): Attribute
    {
        return Attribute::make(
            get: fn () => is_array($this->itens_verificados) ? count($this->itens_verificados) : 0,
        );
    }
}
