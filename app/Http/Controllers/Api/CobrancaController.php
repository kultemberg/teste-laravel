<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListarCobrancasRequest;
use App\Models\Cobranca;
use Illuminate\Http\JsonResponse;

class CobrancaController extends Controller
{
    public function index(ListarCobrancasRequest $request): JsonResponse
    {
        $ordenarPor = $request->input('ordenar_por', 'data_vencimento');
        $direcao = $request->input('direcao', 'asc');
        $porPagina = (int)$request->input('por_pagina', 15);

        $query = Cobranca::query()
            ->select('cobrancas.*')
            ->join('clientes', 'clientes.id', '=', 'cobrancas.cliente_id')
            ->with(['cliente', 'contrato']);

        if ($request->filled('status')) {
            $query->whereIn('cobrancas.status', $request->input('status'));
        }

        if ($request->filled('data_referencia_inicial')) {
            $query->whereDate('cobrancas.data_referencia', '>=', $request->input('data_referencia_inicial'));
        }

        if ($request->filled('data_referencia_final')) {
            $query->whereDate('cobrancas.data_referencia', '<=', $request->input('data_referencia_final'));
        }

        if ($request->filled('busca')) {
            $busca = $request->input('busca');

            $query->where(function ($subQuery) use ($busca) {
                $subQuery->where('clientes.nome', 'like', "%{$busca}%")
                    ->orWhere('clientes.documento', 'like', "%{$busca}%");
            });
        }

        $query->orderBy("cobrancas.{$ordenarPor}", $direcao);

        $cobrancas = $query->paginate($porPagina)->appends($request->query());

        return response()->json($cobrancas);
    }
}