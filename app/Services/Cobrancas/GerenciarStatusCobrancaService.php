<?php

namespace App\Services\Cobrancas;

use App\Enums\StatusCobranca;
use App\Exceptions\Dominio\SaldoCreditoInsuficienteException;
use App\Exceptions\Dominio\TransicaoCobrancaInvalidaException;
use App\Models\Cobranca;
use App\Models\TransacaoCredito;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

class GerenciarStatusCobrancaService
{
    public function executar(
        Cobranca $cobranca,
        StatusCobranca $novoStatus,
        ?Usuario $usuario = null,
        ?string $motivoCancelamento = null
    ): Cobranca {
        return DB::transaction(function () use ($cobranca, $novoStatus, $usuario, $motivoCancelamento) {
            $statusAtual = $cobranca->status;

            $this->validarTransicao($cobranca, $novoStatus, $motivoCancelamento);

            if ($novoStatus === StatusCobranca::PAGO) {
                $this->aplicarCreditoAutomaticamente($cobranca, $usuario);
            }

            if ($novoStatus === StatusCobranca::CANCELADO) {
                $cobranca->motivo_cancelamento = $motivoCancelamento;
            }

            $cobranca->status = $this->determinarStatusFinal($cobranca, $novoStatus);
            $cobranca->save();

            \Illuminate\Support\Facades\Cache::forget('dashboard_financeiro');

            return $cobranca->fresh();
        });
    }

    private function validarTransicao(
        Cobranca $cobranca,
        StatusCobranca $novoStatus,
        ?string $motivoCancelamento
    ): void {
        $statusAtual = $cobranca->status;

        if ($statusAtual === StatusCobranca::CANCELADO) {
            throw TransicaoCobrancaInvalidaException::dePara($statusAtual->value, $novoStatus->value);
        }

        if ($statusAtual === StatusCobranca::PAGO && $novoStatus !== StatusCobranca::PAGO) {
            throw TransicaoCobrancaInvalidaException::dePara($statusAtual->value, $novoStatus->value);
        }

        if ($novoStatus === StatusCobranca::INADIMPLENTE && now()->toDateString() <= $cobranca->data_vencimento->toDateString()) {
            throw TransicaoCobrancaInvalidaException::inadimplenciaAntesDoVencimento();
        }

        if ($novoStatus === StatusCobranca::CANCELADO && blank($motivoCancelamento)) {
            throw TransicaoCobrancaInvalidaException::cancelamentoSemMotivo();
        }
    }

    private function aplicarCreditoAutomaticamente(Cobranca $cobranca, ?Usuario $usuario = null): void
    {
        $cliente = $cobranca->cliente()->lockForUpdate()->first();

        $valorEmAberto = (float) $cobranca->valor - (float) $cobranca->valor_pago - (float) $cobranca->valor_credito_aplicado;

        if ($valorEmAberto <= 0) {
            return;
        }

        $saldoDisponivel = (float) $cliente->saldo_credito;

        if ($saldoDisponivel <= 0) {
            return;
        }

        $valorAplicado = min($saldoDisponivel, $valorEmAberto);

        if ($valorAplicado > $saldoDisponivel) {
            throw SaldoCreditoInsuficienteException::paraValorDisponivel($saldoDisponivel, $valorAplicado);
        }

        $saldoAnterior = $saldoDisponivel;
        $saldoNovo = $saldoAnterior - $valorAplicado;

        $cliente->saldo_credito = $saldoNovo;
        $cliente->save();

        $cobranca->valor_credito_aplicado += $valorAplicado;

        TransacaoCredito::create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario?->id,
            'cobranca_id' => $cobranca->id,
            'tipo' => 'credito_aplicado_cobranca',
            'valor' => $valorAplicado,
            'saldo_anterior' => $saldoAnterior,
            'saldo_novo' => $saldoNovo,
            'descricao' => 'Aplicação automática de crédito na cobrança.',
            'created_at' => now(),
        ]);
    }

    private function determinarStatusFinal(Cobranca $cobranca, StatusCobranca $novoStatus): StatusCobranca
    {
        if ($novoStatus === StatusCobranca::PAGO) {
            $totalQuitado = (float) $cobranca->valor_pago + (float) $cobranca->valor_credito_aplicado;

            if ($totalQuitado <= 0) {
                return StatusCobranca::AGUARDANDO_PAGAMENTO;
            }

            if ($totalQuitado < (float) $cobranca->valor) {
                return StatusCobranca::PAGO_PARCIAL;
            }

            return StatusCobranca::PAGO;
        }

        return $novoStatus;
    }
}