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
        if (!$this->has('symbol')) {
            return;
        }

        $symbol = $this->input('symbol');

        if (!is_string($symbol)) {
            return;
        }

        $this->merge([
            'symbol' => strtoupper($symbol),
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
