<?php

namespace App\Models;

use App\Enum\Pneu\LocalPneuEnum;
use App\Enum\Pneu\StatusPneuEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\Schema;

class Pneu extends Model
{
    protected $casts = [
        'local' => LocalPneuEnum::class,
        'status' => StatusPneuEnum::class,
        'ciclo_vida' => 'integer',
        'data_aquisicao' => 'date',
        'recapavel' => 'boolean',
        'valor' => 'decimal:2',
        'sulco_inicial' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $pneu): void {
            $pneu->syncCatalogFields();
        });
    }

    public function desenhoPneu(): BelongsTo
    {
        return $this->belongsTo(DesenhoPneu::class, 'desenho_pneu_id');
    }

    public function marcaCatalogo(): BelongsTo
    {
        return $this->belongsTo(PneuMarca::class, 'pneu_marca_id');
    }

    public function modeloCatalogo(): BelongsTo
    {
        return $this->belongsTo(PneuModelo::class, 'pneu_modelo_id');
    }

    public function medidaCatalogo(): BelongsTo
    {
        return $this->belongsTo(PneuMedida::class, 'pneu_medida_id');
    }

    public function localCatalogo(): BelongsTo
    {
        return $this->belongsTo(PneuLocal::class, 'pneu_local_id');
    }

    public function fornecedorCompra(): BelongsTo
    {
        return $this->belongsTo(Parceiro::class, 'fornecedor_compra_id');
    }

    public function consertos(): HasMany
    {
        return $this->hasMany(Conserto::class, 'pneu_id');
    }

    public function recapagens(): HasMany
    {
        return $this->hasMany(Recapagem::class, 'pneu_id');
    }

    public function veiculo(): HasOneThrough
    {
        return $this->hasOneThrough(
            Veiculo::class,
            PneuPosicaoVeiculo::class,
            'pneu_id',
            'id',
            'id',
            'veiculo_id'
        )->withDefault([
            'id' => 0,
            'placa' => 'Não Aplicado',
        ]);
    }

    public function posicaoVeiculo(): HasOne
    {
        return $this->hasOne(PneuPosicaoVeiculo::class, 'pneu_id')
            ->withDefault([
                'id' => 0,
                'posicao' => 'N/A',
                'eixo' => 'N/A',
            ]);
    }

    public function ultimoRecap()
    {
        return $this->hasOne(Recapagem::class, 'pneu_id')->latestOfMany();
    }

    public function historicoMovimentacao(): HasMany
    {
        return $this->hasMany(HistoricoMovimentoPneu::class, 'pneu_id');
    }

    public function ciclos(): HasMany
    {
        return $this->hasMany(PneuCiclo::class, 'pneu_id');
    }

    public function cicloAtual(): HasOne
    {
        return $this->hasOne(PneuCiclo::class, 'pneu_id')->ofMany('numero', 'max');
    }

    public function inspecoes(): HasMany
    {
        return $this->hasMany(PneuInspecao::class, 'pneu_id');
    }

    public function ultimaInspecao(): HasOne
    {
        return $this->hasOne(PneuInspecao::class, 'pneu_id')->latestOfMany('data_inspecao');
    }

    protected function kmPercorridoCiclo(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): int => $this->historicoMovimentacao()
                ->where('ciclo_vida', $this->ciclo_vida)
                ->sum('km_percorrido'),
        );
    }

    protected function kmPercorrido(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes): int => $this->historicoMovimentacao()
                ->sum('km_percorrido'),
        );
    }

    private function syncCatalogFields(): void
    {
        if (! Schema::hasTable('pneu_marcas')) {
            return;
        }

        if ($this->pneu_marca_id) {
            $this->marca = PneuMarca::query()->whereKey($this->pneu_marca_id)->value('nome') ?? $this->marca;
        } elseif ($this->marca) {
            $this->pneu_marca_id = PneuMarca::query()->where('nome', $this->marca)->value('id');
        }

        if ($this->pneu_modelo_id) {
            $this->modelo = PneuModelo::query()->whereKey($this->pneu_modelo_id)->value('nome') ?? $this->modelo;
        } elseif ($this->modelo) {
            $this->pneu_modelo_id = PneuModelo::query()
                ->where('nome', $this->modelo)
                ->when($this->pneu_marca_id, fn ($query) => $query->where('pneu_marca_id', $this->pneu_marca_id))
                ->value('id');
        }

        if ($this->pneu_medida_id) {
            $this->medida = PneuMedida::query()->whereKey($this->pneu_medida_id)->value('codigo') ?? $this->medida;
        } elseif ($this->medida) {
            $this->pneu_medida_id = PneuMedida::query()->where('codigo', $this->medida)->value('id');
        }

        if ($this->pneu_local_id) {
            $this->local = PneuLocal::query()->whereKey($this->pneu_local_id)->value('nome') ?? $this->local;
        } elseif ($this->local) {
            $this->pneu_local_id = PneuLocal::query()->where('nome', $this->local instanceof LocalPneuEnum ? $this->local->value : $this->local)->value('id');
        }
    }
}
