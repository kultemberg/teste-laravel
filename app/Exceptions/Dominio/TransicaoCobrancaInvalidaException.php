<?php

namespace App\Exceptions\Dominio;

use Exception;

class TransicaoCobrancaInvalidaException extends Exception
{
    public static function dePara(string $statusAtual, string $novoStatus): self
    {
        return new self("Transição inválida de '{$statusAtual}' para '{$novoStatus}'.");
    }

    public static function inadimplenciaAntesDoVencimento(): self
    {
        return new self('Não é permitido marcar a cobrança como inadimplente antes da data de vencimento.');
    }

    public static function cancelamentoSemMotivo(): self
    {
        return new self('O cancelamento da cobrança exige um motivo obrigatório.');
    }
}