<?php

namespace App\Http\Requests\Investment;

use App\Models\Investment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvestmentPriceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $investment = $this->route('investment');

        return $investment instanceof Investment && $this->user()?->can('update', $investment) === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }
}
