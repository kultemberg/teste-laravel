<?php

namespace App\Jobs;

use App\Models\Cliente;
use App\Models\Cobranca;
use App\Models\TransacaoCredito;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AplicarCreditoPendente implements ShouldQueue
{
    use Queueable;

    public array $backoff = [60, 300, 1800];
    public int $tries = 4;

    public function __construct(public int $clienteId)
    {
    }

    public function handle(): void
    {
        DB::transaction(function () {
            $cliente = Cliente::query()
                ->lockForUpdate()
                ->find($this->clienteId);

            if (!$cliente) {
                return;
            }

            $cobranca = Cobranca::query()
                ->where('cliente_id', $cliente->id)
                ->whereIn('status', ['aguardando_pagamento', 'pago_parcial'])
                ->orderBy('data_vencimento')
                ->lockForUpdate()
                ->first();

            if (!$cobranca) {
                return;
            }

            if ((float)$cliente->saldo_credito <= 0) {
                return;
            }

            $valorEmAberto = (float)$cobranca->valor - (float)$cobranca->valor_pago - (float)$cobranca->valor_credito_aplicado;

            if ($valorEmAberto <= 0) {
                return;
            }

            $valorAplicado = min((float)$cliente->saldo_credito, $valorEmAberto);

            $jaExisteTransacao = TransacaoCredito::query()
                ->where('cobranca_id', $cobranca->id)
                ->where('tipo', 'credito_aplicado_cobranca')
                ->where('valor', $valorAplicado)
                ->exists();

            if ($jaExisteTransacao) {
                return;
            }

            $saldoAnterior = (float)$cliente->saldo_credito;
            $saldoNovo = $saldoAnterior - $valorAplicado;

            $cliente->saldo_credito = $saldoNovo;
            $cliente->save();

            $cobranca->valor_credito_aplicado = (float)$cobranca->valor_credito_aplicado + $valorAplicado;

            $totalQuitado = (float)$cobranca->valor_pago + (float)$cobranca->valor_credito_aplicado;

            if ($totalQuitado >= (float)$cobranca->valor) {
                $cobranca->status = 'pago';
            }
            elseif ($totalQuitado > 0) {
                $cobranca->status = 'pago_parcial';
            }

            $cobranca->save();

            \Illuminate\Support\Facades\Cache::forget('dashboard_financeiro');

            TransacaoCredito::create([
                'cliente_id' => $cliente->id,
                'usuario_id' => null,
                'cobranca_id' => $cobranca->id,
                'tipo' => 'credito_aplicado_cobranca',
                'valor' => $valorAplicado,
                'saldo_anterior' => $saldoAnterior,
                'saldo_novo' => $saldoNovo,
                'descricao' => 'Crédito aplicado automaticamente na cobrança',
                'created_at' => now(),
            ]);
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Falha definitiva no job AplicarCreditoPendente.', [
            'cliente_id' => $this->clienteId,
            'erro' => $exception->getMessage(),
        ]);
    }
}