<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\DB;

class DashboardFinanceiroService
{
    public function executar(): array
    {
        $inicioMesAtual = now()->startOfMonth()->toDateString();
        $fimMesAtual = now()->endOfMonth()->toDateString();

        $inicioMesAnterior = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $fimMesAnterior = now()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $faturadoMesAtual = (float)DB::table('cobrancas')
            ->whereBetween('data_referencia', [$inicioMesAtual, $fimMesAtual])
            ->whereIn('status', ['pago', 'pago_parcial'])
            ->sum(DB::raw('valor_pago + valor_credito_aplicado'));

        $faturadoMesAnterior = (float)DB::table('cobrancas')
            ->whereBetween('data_referencia', [$inicioMesAnterior, $fimMesAnterior])
            ->whereIn('status', ['pago', 'pago_parcial'])
            ->sum(DB::raw('valor_pago + valor_credito_aplicado'));

        $variacaoPercentual = 0.0;

        if ($faturadoMesAnterior > 0) {
            $variacaoPercentual = (($faturadoMesAtual - $faturadoMesAnterior) / $faturadoMesAnterior) * 100;
        }
        elseif ($faturadoMesAtual > 0) {
            $variacaoPercentual = 100.0;
        }

        $totalEmAberto = (float)DB::table('cobrancas')
            ->whereIn('status', ['aguardando_pagamento', 'pago_parcial'])
            ->sum(DB::raw('valor - valor_pago - valor_credito_aplicado'));

        $totalInadimplente = (float)DB::table('cobrancas')
            ->where('status', 'inadimplente')
            ->sum(DB::raw('valor - valor_pago - valor_credito_aplicado'));

        $topClientes = DB::table('clientes')
            ->join('contratos', 'contratos.cliente_id', '=', 'clientes.id')
            ->join('itens_contrato', 'itens_contrato.contrato_id', '=', 'contratos.id')
            ->where('contratos.status', 'ativo')
            ->select(
            'clientes.id',
            'clientes.nome',
            'clientes.documento',
            DB::raw('SUM(itens_contrato.quantidade * itens_contrato.valor_unitario) as valor_contrato_ativo')
        )
            ->groupBy('clientes.id', 'clientes.nome', 'clientes.documento')
            ->orderByDesc('valor_contrato_ativo')
            ->limit(5)
            ->get();

        $distribuicaoOrdensServico = DB::table('ordens_servico')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        return [
            'faturamento' => [
                'mes_atual' => round($faturadoMesAtual, 2),
                'mes_anterior' => round($faturadoMesAnterior, 2),
                'variacao_percentual' => round($variacaoPercentual, 2),
            ],
            'totais' => [
                'em_aberto' => round($totalEmAberto, 2),
                'inadimplente' => round($totalInadimplente, 2),
            ],
            'top_clientes' => $topClientes,
            'distribuicao_ordens_servico' => $distribuicaoOrdensServico,
        ];
    }
}