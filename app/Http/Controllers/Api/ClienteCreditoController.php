<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AplicarCreditoRequest;
use App\Models\Cliente;
use App\Services\Clientes\AplicarCreditoClienteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClienteCreditoController extends Controller
{
    public function aplicarCredito(
        AplicarCreditoRequest $request,
        Cliente $cliente,
        AplicarCreditoClienteService $service
        ): JsonResponse
    {
        $usuario = $request->user();

        if ($usuario->role !== 'financeiro') {
            return response()->json([
                'message' => 'Apenas usuários com role financeiro podem aplicar crédito.'
            ], 403);
        }

        $clienteAtualizado = $service->executar(
            $cliente,
            (float)$request->input('valor'),
            $usuario,
            $request->input('descricao')
        );

        return response()->json([
            'message' => 'Crédito aplicado com sucesso.',
            'cliente' => [
                'id' => $clienteAtualizado->id,
                'nome' => $clienteAtualizado->nome,
                'saldo_credito' => $clienteAtualizado->saldo_credito,
            ]
        ]);
    }
}