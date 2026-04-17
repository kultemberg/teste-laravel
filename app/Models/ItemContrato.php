<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemContrato extends Model
{
    protected $table = 'itens_contrato';

    protected $fillable = [
        'contrato_id',
        'descricao',
        'quantidade',
        'valor_unitario',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class , 'contrato_id');
    }
}