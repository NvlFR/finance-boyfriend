<?php

namespace App\Http\Requests\BirthdaySurprise;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateBirthdaySurpriseRequest extends FormRequest
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
            'opening_message' => ['required', 'string', 'max:300'],
            'appreciation_message' => ['required', 'string', 'max:1000'],
            'love_letter' => ['required', 'string', 'max:3000'],
            'closing_message' => ['required', 'string', 'max:1000'],
            'starts_at' => ['required', 'date_format:Y-m-d\\TH:i'],
            'is_enabled' => ['required', 'boolean'],
            'photos' => ['nullable', 'array', 'max:6'],
            'photos.*' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'kept_photos' => ['nullable', 'array', 'max:6'],
            'kept_photos.*' => ['required', 'string', 'max:255'],
            'vouchers' => ['nullable', 'array', 'max:3'],
            'vouchers.*' => ['nullable', 'string', 'max:100'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $photoCount = count($this->file('photos', [])) + count($this->input('kept_photos', []));

                if ($photoCount > 6) {
                    $validator->errors()->add('photos', 'Maksimal enam foto untuk satu surprise.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_enabled' => $this->boolean('is_enabled')]);
    }
}
