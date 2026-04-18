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

        // ─── Query 1 (única): todos os agregados financeiros em um único SELECT ─
        // Usa CASE WHEN para calcular faturamento dos dois meses + aberto +
        // inadimplente em uma passagem só pela tabela cobrancas — zero N+1.
        $totais = DB::selectOne("
            SELECT
                COALESCE(SUM(CASE
                    WHEN status IN ('pago', 'pago_parcial')
                     AND data_referencia BETWEEN :inicio_atual AND :fim_atual
                    THEN valor_pago + valor_credito_aplicado
                    ELSE 0
                END), 0) AS faturado_mes_atual,

                COALESCE(SUM(CASE
                    WHEN status IN ('pago', 'pago_parcial')
                     AND data_referencia BETWEEN :inicio_anterior AND :fim_anterior
                    THEN valor_pago + valor_credito_aplicado
                    ELSE 0
                END), 0) AS faturado_mes_anterior,

                COALESCE(SUM(CASE
                    WHEN status IN ('aguardando_pagamento', 'pago_parcial')
                    THEN valor - valor_pago - valor_credito_aplicado
                    ELSE 0
                END), 0) AS total_em_aberto,

                COALESCE(SUM(CASE
                    WHEN status = 'inadimplente'
                    THEN valor - valor_pago - valor_credito_aplicado
                    ELSE 0
                END), 0) AS total_inadimplente

            FROM cobrancas
        ", [
            'inicio_atual' => $inicioMesAtual,
            'fim_atual' => $fimMesAtual,
            'inicio_anterior' => $inicioMesAnterior,
            'fim_anterior' => $fimMesAnterior,
        ]);

        // ─── Query 2: top 5 clientes por valor de contrato ativo ─────────────
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

        // ─── Query 3: distribuição de ordens de serviço por status ───────────
        $distribuicaoOrdensServico = DB::table('ordens_servico')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        // ─── Variação percentual (calculada em PHP, sem query extra) ──────────
        $faturadoMesAtual = (float)$totais->faturado_mes_atual;
        $faturadoMesAnterior = (float)$totais->faturado_mes_anterior;

        $variacaoPercentual = match (true) {
                $faturadoMesAnterior > 0 => (($faturadoMesAtual - $faturadoMesAnterior) / $faturadoMesAnterior) * 100,
                $faturadoMesAtual > 0 => 100.0,
                default => 0.0,
            };

        return [
            'faturamento' => [
                'mes_atual' => round($faturadoMesAtual, 2),
                'mes_anterior' => round($faturadoMesAnterior, 2),
                'variacao_percentual' => round($variacaoPercentual, 2),
            ],
            'totais' => [
                'em_aberto' => round((float)$totais->total_em_aberto, 2),
                'inadimplente' => round((float)$totais->total_inadimplente, 2),
            ],
            'top_clientes' => $topClientes,
            'distribuicao_ordens_servico' => $distribuicaoOrdensServico,
        ];
    }
}