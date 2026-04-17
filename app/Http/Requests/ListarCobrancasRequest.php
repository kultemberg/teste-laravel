<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListarCobrancasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'array'],
            'status.*' => ['string'],
            'data_referencia_inicial' => ['nullable', 'date'],
            'data_referencia_final' => ['nullable', 'date'],
            'busca' => ['nullable', 'string'],
            'ordenar_por' => ['nullable', 'string', 'in:id,data_referencia,data_vencimento,valor,status,created_at'],
            'direcao' => ['nullable', 'string', 'in:asc,desc'],
            'por_pagina' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}