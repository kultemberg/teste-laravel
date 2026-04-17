<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoricoStatusOrdem extends Model
{
    protected $table = 'historico_status_ordens';

    public $timestamps = false;

    protected $fillable = [
        'ordem_servico_id',
        'usuario_id',
        'status_anterior',
        'status_novo',
        'created_at',
    ];

    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class , 'ordem_servico_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class , 'usuario_id');
    }
}