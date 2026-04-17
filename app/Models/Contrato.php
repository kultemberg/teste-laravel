<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contrato extends Model
{
    protected $table = 'contratos';

    protected $fillable = [
        'cliente_id',
        'data_inicio',
        'data_encerramento',
        'dia_vencimento',
        'status',
        'observacoes',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class , 'cliente_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemContrato::class , 'contrato_id');
    }

    public function cobrancas(): HasMany
    {
        return $this->hasMany(Cobranca::class , 'contrato_id');
    }

    public function ordensServico(): HasMany
    {
        return $this->hasMany(OrdemServico::class , 'contrato_id');
    }
}