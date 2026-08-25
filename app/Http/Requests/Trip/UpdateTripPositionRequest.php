<?php

namespace App\Http\Requests\Trip;

use App\Models\Trip;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTripPositionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $trip = $this->route('trip');
        $user = $this->user();

        return $trip instanceof Trip
            && $user !== null
            && $trip->user_id === $user->id
            && $trip->couple_space_id === $user->current_couple_space_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'speed' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:10000'],
        ];
    }
}
