<?php

namespace App\Http\Requests\Investment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvestmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['nullable', 'string', 'max:30'],
            'asset_type' => ['required', Rule::in(['stock', 'mutual_fund', 'crypto', 'gold', 'deposit', 'other'])],
            'scope' => ['required', Rule::in(['personal', 'shared'])],
        ];
    }
}
