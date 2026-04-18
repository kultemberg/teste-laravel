<?php

namespace App\Observers;

use App\Models\Cobranca;
use Illuminate\Support\Facades\Cache;

class CobrancaObserver
{
    /**
     * Invalida o cache do dashboard sempre que uma cobrança for criada,
     * atualizada ou deletada — garantindo consistência independente do
     * caminho que disparou a mudança (Service, Job, Tinker, etc).
     */
    public function created(Cobranca $cobranca): void
    {
        Cache::forget('dashboard_financeiro');
    }

    public function updated(Cobranca $cobranca): void
    {
        // Invalida somente se o status tiver mudado, evitando flush
        // desnecessário em atualizações que não afetam o dashboard.
        if ($cobranca->wasChanged('status') || $cobranca->wasChanged('valor_pago') || $cobranca->wasChanged('valor_credito_aplicado')) {
            Cache::forget('dashboard_financeiro');
        }
    }

    public function deleted(Cobranca $cobranca): void
    {
        Cache::forget('dashboard_financeiro');
    }
}