<?php

namespace App\Http\Requests\Transaction;

use App\Models\Category;
use App\Models\Wallet;
use Brick\Math\BigDecimal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
        $space = $this->user()?->currentCoupleSpace;
        $spaceId = $space?->id;
        $memberIds = $space ? array_filter([$space->user_one_id, $space->user_two_id]) : [];

        return [
            'wallet_id' => ['required', 'integer', Rule::exists('wallets', 'id')->where('couple_space_id', $spaceId)],
            'to_wallet_id' => ['nullable', 'required_if:type,transfer', 'integer', Rule::exists('wallets', 'id')->where('couple_space_id', $spaceId), 'different:wallet_id'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->where(fn ($categoryQuery) => $categoryQuery
                        ->whereNull('couple_space_id')
                        ->orWhere('couple_space_id', $spaceId))),
            ],
            'type' => ['required', 'in:income,expense,transfer'],
            'scope' => ['required', 'in:personal,shared'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'fee_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0'],
            'transaction_date' => ['required', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'receipt_image_path' => ['nullable', 'string', 'max:255'],
            'client_reference' => ['nullable', 'string', 'max:64'],
            'source_type' => ['nullable', 'required_with:source_id', Rule::in(['subscription', 'wishlist', 'budget'])],
            'source_id' => ['nullable', 'required_with:source_type', 'integer'],

            // Split bill details (optional or required when scope=shared)
            'split' => ['nullable', 'array'],
            'split.paid_by_user_id' => ['nullable', 'integer', Rule::in($memberIds)],
            'split.split_type' => ['nullable', 'in:full_one,full_two,split_equal,custom,joint_fund'],
            'split.user_one_amount' => ['nullable', 'numeric', 'min:0'],
            'split.user_two_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('type') === 'transfer') {
            $this->merge(['category_id' => null]);

            return;
        }

        $this->merge(['fee_amount' => 0]);
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $categoryId = $this->integer('category_id');
                $transactionType = $this->input('type');

                if ($categoryId && $transactionType !== 'transfer') {
                    $categoryType = Category::whereKey($categoryId)->value('type');

                    if ($categoryType !== 'both' && $categoryType !== $transactionType) {
                        $validator->errors()->add('category_id', 'Kategori tidak sesuai dengan tipe transaksi.');
                    }
                }

                $walletId = $this->integer('wallet_id');
                if ($walletId) {
                    $wallet = Wallet::query()->find($walletId);

                    if ($wallet && $wallet->type === 'personal' && $wallet->user_id !== $this->user()?->id) {
                        $validator->errors()->add('wallet_id', 'Dompet pribadi pasangan tidak dapat dipakai sebagai sumber transaksi.');
                    }
                }

                if ($this->input('type') !== 'expense' || $this->input('scope') !== 'shared') {
                    return;
                }

                if ($this->input('split.split_type') === 'custom') {
                    $splitTotal = BigDecimal::of((string) $this->input('split.user_one_amount', 0))
                        ->plus((string) $this->input('split.user_two_amount', 0));
                    $amount = BigDecimal::of((string) $this->input('amount'));

                    if (! $splitTotal->isEqualTo($amount)) {
                        $validator->errors()->add('split', 'Total pembagian harus sama dengan nominal transaksi.');
                    }
                }
            },
        ];
    }
}
