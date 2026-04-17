<?php

namespace App\Enums;

enum StatusCobranca: string
{
    case AGUARDANDO_PAGAMENTO = 'aguardando_pagamento';
    case PAGO_PARCIAL = 'pago_parcial';
    case PAGO = 'pago';
    case INADIMPLENTE = 'inadimplente';
    case CANCELADO = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::AGUARDANDO_PAGAMENTO => 'Aguardando pagamento',
            self::PAGO_PARCIAL => 'Pago parcial',
            self::PAGO => 'Pago',
            self::INADIMPLENTE => 'Inadimplente',
            self::CANCELADO => 'Cancelado',
        };
    }

    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}