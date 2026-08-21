<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'to_wallet_id' => ['nullable', 'required_if:type,transfer', 'integer', 'exists:wallets,id', 'different:wallet_id'],
            'category_id' => ['nullable', 'required_unless:type,transfer', 'integer', 'exists:categories,id'],
            'type' => ['required', 'in:income,expense,transfer'],
            'scope' => ['required', 'in:personal,shared'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'receipt_image_path' => ['nullable', 'string', 'max:255'],

            // Split bill details (optional or required when scope=shared)
            'split' => ['nullable', 'array'],
            'split.paid_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'split.split_type' => ['nullable', 'in:full_one,full_two,split_equal,custom,joint_fund'],
            'split.user_one_amount' => ['nullable', 'numeric', 'min:0'],
            'split.user_two_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
