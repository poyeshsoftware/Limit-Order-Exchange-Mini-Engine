<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $symbol = $this->input('symbol');
        $side = $this->input('side');

        $this->merge([
            'symbol' => is_string($symbol) ? strtoupper($symbol) : $symbol,
            'side' => is_string($side) ? strtolower($side) : $side,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'symbol' => ['required', 'string', Rule::in(['BTC', 'ETH'])],
            'side' => ['required', 'string', Rule::in(['buy', 'sell'])],
            'price' => ['required', 'numeric', 'gt:0', 'decimal:0,8'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,8'],
        ];
    }
}
