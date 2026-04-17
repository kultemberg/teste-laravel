<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardFinanceiroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DashboardFinanceiroController extends Controller
{
    public function index(DashboardFinanceiroService $service): JsonResponse
    {
        $dados = Cache::remember('dashboard_financeiro', 300, function () use ($service) {
            return $service->executar();
        });

        return response()->json($dados);
    }
}