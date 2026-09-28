<?php

namespace App\Http\Requests\Wallet;

use App\Models\Wallet;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWalletRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $wallet = $this->route('wallet');
        $user = $this->user();

        return $user && $wallet instanceof Wallet
            && $wallet->couple_space_id === $user->current_couple_space_id
            && ($wallet->type === 'joint' || $wallet->user_id === $user->id);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'wallet_type' => ['sometimes', 'required', 'in:bank,ewallet,cash,investment,credit_card'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'balance' => ['sometimes', 'required', 'numeric', 'min:0'],
            'expected_balance' => ['required_with:balance', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'color' => ['nullable', 'string', 'max:20'],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
