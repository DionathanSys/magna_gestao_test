<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enum\Frete\TipoDocumentoEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentoFrete extends Model
{
    protected $table = 'documentos_frete';

    protected $casts = [
        'tipo_documento' => TipoDocumentoEnum::class,
        'valor_total' => MoneyCast::class,
        'valor_icms' => MoneyCast::class,
        'valor_liquido' => MoneyCast::class,
    ];

    protected $appends = ['descricao'];

    public function viagem(): BelongsTo
    {
        return $this->belongsTo(Viagem::class, 'viagem_id', 'id');
    }

     /**
     * Relação com o modelo ResultadoPeriodo
     *
     * @return BelongsTo
     */
    public function resultadoPeriodo(): BelongsTo
    {
        return $this->belongsTo(ResultadoPeriodo::class);
    }

    public function viagemBugio(): HasMany
    {
        return $this->hasMany(ViagemBugio::class, 'documento_frete_id', 'id');
    }

    public function veiculo(): BelongsTo
    {
        return $this->belongsTo(Veiculo::class, 'veiculo_id');
    }

    public function integrado(): BelongsTo
    {
        return $this->belongsTo(Integrado::class, 'integrado_id');
    }

    public function scopeSemVinculoViagem(Builder $query): Builder
    {
        return $query->where('viagem_id', null);
    }

    public function descricao(): Attribute
    {
        $dataEmissaoFormatada = Carbon::parse($this->attributes['data_emissao'])->format('d/m/Y');
        return Attribute::make(
            get: fn () => "Nº {$this->numero_documento} {$dataEmissaoFormatada} - {$this->parceiro_destino}",
        );
    }

    public function dataReferencia(): Attribute
    {
        return Attribute::make(
            get: fn () => Carbon::parse($this->attributes['data_emissao'])->format('d/m/Y'),
        );
    }
}
