<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TradeIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $symbol = $this->input('symbol');

        $this->merge([
            'symbol' => is_string($symbol) ? strtoupper($symbol) : $symbol,
        ]);
    }

    public function rules(): array
    {
        return [
            'symbol' => ['required', 'string', Rule::in(['BTC', 'ETH'])],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:200'],
        ];
    }
}

