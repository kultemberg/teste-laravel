<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdemServico extends Model
{
    protected $table = 'ordens_servico';

    protected $fillable = [
        'contrato_id',
        'usuario_id',
        'titulo',
        'descricao',
        'horas_estimadas',
        'horas_realizadas',
        'nivel_prioridade',
        'status',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class , 'contrato_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class , 'usuario_id');
    }

    public function historicoStatus(): HasMany
    {
        return $this->hasMany(HistoricoStatusOrdem::class , 'ordem_servico_id');
    }
}