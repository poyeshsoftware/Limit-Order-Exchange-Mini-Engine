<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MyOrdersRequest extends FormRequest
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
            'symbol' => ['sometimes', 'string', Rule::in(['BTC', 'ETH'])],
        ];
    }
}
