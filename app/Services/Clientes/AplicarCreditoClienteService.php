<?php

namespace App\Services\Clientes;

use App\Jobs\AplicarCreditoPendente;
use App\Models\Cliente;
use App\Models\TransacaoCredito;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AplicarCreditoClienteService
{
    public function executar(
        Cliente $cliente,
        float $valor,
        Usuario $usuario,
        ?string $descricao = null
        ): Cliente
    {
        return DB::transaction(function () use ($cliente, $valor, $usuario, $descricao) {
            $cliente = Cliente::query()
                ->lockForUpdate()
                ->findOrFail($cliente->id);

            $saldoAnterior = (float)$cliente->saldo_credito;
            $saldoNovo = $saldoAnterior + $valor;

            $cliente->saldo_credito = $saldoNovo;
            $cliente->save();

            TransacaoCredito::create([
                'cliente_id' => $cliente->id,
                'usuario_id' => $usuario->id,
                'cobranca_id' => null,
                'tipo' => 'credito_manual',
                'valor' => $valor,
                'saldo_anterior' => $saldoAnterior,
                'saldo_novo' => $saldoNovo,
                'descricao' => $descricao,
                'created_at' => now(),
            ]);

            Log::info('Crédito manual aplicado ao cliente.', [
                'cliente_id' => $cliente->id,
                'usuario_id' => $usuario->id,
                'aplicado_em' => now()->toDateTimeString(),
                'saldo_anterior' => $saldoAnterior,
                'saldo_novo' => $saldoNovo,
                'valor_credito' => $valor,
            ]);

            AplicarCreditoPendente::dispatch($cliente->id);

            return $cliente->fresh();
        });
    }
}