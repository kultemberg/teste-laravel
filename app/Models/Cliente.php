<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'nome',
        'documento',
        'telefone',
        'email',
        'saldo_credito',
    ];

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class , 'cliente_id');
    }

    public function cobrancas(): HasMany
    {
        return $this->hasMany(Cobranca::class , 'cliente_id');
    }

    public function transacoesCredito(): HasMany
    {
        return $this->hasMany(TransacaoCredito::class , 'cliente_id');
    }
}