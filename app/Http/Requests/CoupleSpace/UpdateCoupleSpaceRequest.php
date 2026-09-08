<?php

namespace App\Http\Requests\CoupleSpace;

use App\Models\CoupleSpace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCoupleSpaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $coupleSpace = $this->route('coupleSpace');
        $userId = $this->user()?->id;

        return $coupleSpace instanceof CoupleSpace
            && $userId !== null
            && in_array($userId, [$coupleSpace->user_one_id, $coupleSpace->user_two_id], true);
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
            'anniversary_date' => ['nullable', 'date'],
            'dashboard_cover' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'dashboard_cover.image' => 'Cover dashboard harus berupa gambar.',
            'dashboard_cover.mimes' => 'Cover dashboard harus berformat JPG, PNG, atau WebP.',
            'dashboard_cover.max' => 'Ukuran cover dashboard maksimal 5 MB.',
        ];
    }
}
