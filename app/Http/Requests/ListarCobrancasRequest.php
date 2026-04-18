<?php

namespace App\Http\Requests;

use App\Enums\StatusCobranca;
use Illuminate\Foundation\Http\FormRequest;

class ListarCobrancasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statusValidos = implode(',', StatusCobranca::valores());

        return [
            'status' => ['nullable', 'array'],
            'status.*' => ['string', "in:{$statusValidos}"],
            'data_referencia_inicial' => ['nullable', 'date'],
            'data_referencia_final' => ['nullable', 'date'],
            'busca' => ['nullable', 'string', 'max:100'],
            'ordenar_por' => ['nullable', 'string', 'in:id,data_referencia,data_vencimento,valor,status,created_at'],
            'direcao' => ['nullable', 'string', 'in:asc,desc'],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        $statusValidos = implode(', ', StatusCobranca::valores());

        return [
            'status.*.in' => "Cada status deve ser um dos valores: {$statusValidos}.",
            'ordenar_por.in' => 'Campo de ordenação inválido.',
            'direcao.in' => 'A direção deve ser asc ou desc.',
            'busca.max' => 'O termo de busca não pode ter mais de 100 caracteres.',
            'por_pagina.min' => 'O mínimo de itens por página é 1.',
            'por_pagina.max' => 'O máximo de itens por página é 100.',
        ];
    }
}