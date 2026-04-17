<?php

namespace App\Exceptions\Dominio;

use Exception;

class SaldoCreditoInsuficienteException extends Exception
{
    public static function paraValorDisponivel(float $saldoDisponivel, float $valorSolicitado): self
    {
        return new self("Saldo de crédito insuficiente. Disponível: {$saldoDisponivel}. Solicitado: {$valorSolicitado}.");
    }
}