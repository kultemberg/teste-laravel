<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransacaoCredito extends Model
{
    protected $table = 'transacoes_credito';

    public $timestamps = false;

    protected $fillable = [
        'cliente_id',
        'usuario_id',
        'cobranca_id',
        'tipo',
        'valor',
        'saldo_anterior',
        'saldo_novo',
        'descricao',
        'created_at',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class , 'cliente_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class , 'usuario_id');
    }

    public function cobranca(): BelongsTo
    {
        return $this->belongsTo(Cobranca::class , 'cobranca_id');
    }
}