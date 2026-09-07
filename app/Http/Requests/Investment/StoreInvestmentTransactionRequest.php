<?php

namespace App\Http\Requests\Investment;

use App\Models\Investment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvestmentTransactionRequest extends FormRequest
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
        $spaceId = $this->user()?->current_couple_space_id;
        $userId = $this->user()?->id;

        return [
            'type' => ['required', Rule::in(['buy', 'sell'])],
            'input_mode' => ['required', Rule::in(['quantity', 'amount'])],
            'wallet_id' => [
                'required',
                Rule::exists('wallets', 'id')->where(fn ($query) => $query
                    ->where('couple_space_id', $spaceId)
                    ->where('is_active', true)
                    ->where(fn ($walletQuery) => $walletQuery
                        ->where('type', 'joint')
                        ->orWhere('user_id', $userId))),
            ],
            'quantity' => [
                Rule::requiredIf(fn (): bool => $this->input('type') === 'sell' || $this->input('input_mode') === 'quantity'),
                'nullable',
                'numeric',
                'gt:0',
                'decimal:0,8',
            ],
            'amount' => [
                Rule::requiredIf(fn (): bool => $this->input('type') === 'buy' && $this->input('input_mode') === 'amount'),
                'nullable',
                'numeric',
                'gt:0',
                'decimal:0,2',
            ],
            'unit_price' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'fee_amount' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'transaction_date' => ['required', 'date'],
            'client_reference' => ['required', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'input_mode' => $this->input('input_mode', 'quantity'),
        ]);
    }
}
