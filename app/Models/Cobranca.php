<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cobranca extends Model
{
    protected $table = 'cobrancas';

    protected $fillable = [
        'cliente_id',
        'contrato_id',
        'data_referencia',
        'data_vencimento',
        'valor',
        'valor_pago',
        'valor_credito_aplicado',
        'status',
        'motivo_cancelamento',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class , 'cliente_id');
    }

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class , 'contrato_id');
    }

    public function transacoesCredito(): HasMany
    {
        return $this->hasMany(TransacaoCredito::class , 'cobranca_id');
    }
}