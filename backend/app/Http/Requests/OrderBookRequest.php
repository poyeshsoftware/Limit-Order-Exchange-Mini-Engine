<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderBookRequest extends FormRequest
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
        ];
    }
}

