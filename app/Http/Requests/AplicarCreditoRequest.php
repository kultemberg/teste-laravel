<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AplicarCreditoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'valor' => ['required', 'numeric', 'gt:0'],
            'descricao' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'valor.required' => 'O valor do crédito é obrigatório.',
            'valor.numeric' => 'O valor do crédito deve ser numérico.',
            'valor.gt' => 'O valor do crédito deve ser maior que zero.',
        ];
    }
}