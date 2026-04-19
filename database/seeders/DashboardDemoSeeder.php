<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cliente;
use App\Models\Contrato;
use App\Models\ItemContrato;
use App\Models\Cobranca;
use App\Models\OrdemServico;
use App\Models\Usuario;

class DashboardDemoSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = Usuario::create([
            'nome' => 'Financeiro Seeder',
            'email' => 'financeiro.seeder@teste.com',
            'password' => bcrypt('123456'),
            'role' => 'financeiro',
        ]);
        // Cliente 1
        $cliente1 = Cliente::create([
            'nome' => 'Empresa Alpha',
            'documento' => '11111111111',
            'telefone' => '84999999991',
            'email' => 'alpha@teste.com',
            'saldo_credito' => 0,
        ]);

        // Cliente 2
        $cliente2 = Cliente::create([
            'nome' => 'Empresa Beta',
            'documento' => '22222222222',
            'telefone' => '84999999992',
            'email' => 'beta@teste.com',
            'saldo_credito' => 0,
        ]);

        // Contratos
        $contrato1 = Contrato::create([
            'cliente_id' => $cliente1->id,
            'data_inicio' => now(),
            'data_encerramento' => null,
            'dia_vencimento' => 10,
            'status' => 'ativo',
            'observacoes' => 'Contrato Alpha',
        ]);

        $contrato2 = Contrato::create([
            'cliente_id' => $cliente2->id,
            'data_inicio' => now(),
            'data_encerramento' => null,
            'dia_vencimento' => 10,
            'status' => 'ativo',
            'observacoes' => 'Contrato Beta',
        ]);

        // Itens de contrato (TOP CLIENTES)
        ItemContrato::create([
            'contrato_id' => $contrato1->id,
            'descricao' => 'Sistema completo',
            'quantidade' => 1,
            'valor_unitario' => 5000,
        ]);

        ItemContrato::create([
            'contrato_id' => $contrato2->id,
            'descricao' => 'Manutenção mensal',
            'quantidade' => 2,
            'valor_unitario' => 800,
        ]);

        // Cobranças (FATURAMENTO + ABERTO)
        Cobranca::create([
            'cliente_id' => $cliente1->id,
            'contrato_id' => $contrato1->id,
            'data_referencia' => now(),
            'data_vencimento' => now()->subDays(5),
            'valor' => 5000,
            'valor_pago' => 3000,
            'valor_credito_aplicado' => 0,
            'status' => 'pago_parcial',
        ]);

        Cobranca::create([
            'cliente_id' => $cliente2->id,
            'contrato_id' => $contrato2->id,
            'data_referencia' => now(),
            'data_vencimento' => now()->addDays(5), // vencimento futuro → aguardando pagamento
            'valor' => 1600,
            'valor_pago' => 0,
            'valor_credito_aplicado' => 0,
            'status' => 'aguardando_pagamento',
        ]);

        // Ordens de serviço (DISTRIBUIÇÃO)
        OrdemServico::create([
            'contrato_id' => $contrato1->id,
            'usuario_id' => $usuario->id,
            'titulo' => 'OS 1',
            'descricao' => 'Teste',
            'horas_estimadas' => 5,
            'horas_realizadas' => 2,
            'nivel_prioridade' => 'alta',
            'status' => 'aberta',
        ]);

        OrdemServico::create([
            'contrato_id' => $contrato1->id,
            'usuario_id' => $usuario->id,
            'titulo' => 'OS 2',
            'descricao' => 'Teste',
            'horas_estimadas' => 3,
            'horas_realizadas' => 3,
            'nivel_prioridade' => 'media',
            'status' => 'concluida',
        ]);

        OrdemServico::create([
            'contrato_id' => $contrato2->id,
            'usuario_id' => $usuario->id,
            'titulo' => 'OS 3',
            'descricao' => 'Teste',
            'horas_estimadas' => 4,
            'horas_realizadas' => 1,
            'nivel_prioridade' => 'baixa',
            'status' => 'em_andamento',
        ]);
    }
}