<?php

namespace App\Http\Requests\Settlement;

use App\Models\Wallet;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSettlementRequest extends FormRequest
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
        $space = $this->user()?->currentCoupleSpace;
        $memberIds = $space ? array_filter([$space->user_one_id, $space->user_two_id]) : [];

        return [
            'to_user_id' => ['required', 'integer', Rule::in($memberIds)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'settled_at' => ['nullable', 'date'],
            'payment_mode' => ['nullable', Rule::in(['external', 'wallet_transfer'])],
            'source_wallet_id' => ['nullable', 'required_if:payment_mode,wallet_transfer', 'integer', Rule::exists('wallets', 'id')->where('couple_space_id', $space?->id)],
            'destination_wallet_id' => ['nullable', 'required_if:payment_mode,wallet_transfer', 'integer', 'different:source_wallet_id', Rule::exists('wallets', 'id')->where('couple_space_id', $space?->id)],
            'client_reference' => ['nullable', 'string', 'max:64'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('payment_mode', 'external') !== 'wallet_transfer') {
                    return;
                }

                $sourceWallet = Wallet::query()->find($this->integer('source_wallet_id'));
                $destinationWallet = Wallet::query()->find($this->integer('destination_wallet_id'));

                if ($sourceWallet && $sourceWallet->type !== 'joint' && $sourceWallet->user_id !== $this->user()?->id) {
                    $validator->errors()->add('source_wallet_id', 'Dompet sumber harus milik kamu atau dompet bersama.');
                }

                if ($destinationWallet && $destinationWallet->user_id !== $this->integer('to_user_id')) {
                    $validator->errors()->add('destination_wallet_id', 'Dompet tujuan harus milik pasangan yang menerima pelunasan.');
                }
            },
        ];
    }
}
