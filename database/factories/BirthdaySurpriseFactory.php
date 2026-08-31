<?php

namespace Database\Factories;

use App\Models\BirthdaySurprise;
use App\Models\CoupleSpace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BirthdaySurprise>
 */
class BirthdaySurpriseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $space = CoupleSpace::factory()->active()->create();

        return [
            'couple_space_id' => $space->id,
            'creator_user_id' => $space->user_one_id,
            'recipient_user_id' => $space->user_two_id,
            'opening_message' => 'Hari ini ada sesuatu yang spesial untuk kamu.',
            'appreciation_message' => 'Terima kasih untuk semua perhatian kecil dan tawa yang kita bagi.',
            'love_letter' => 'Kehadiranmu adalah hadiah terbaik dalam hidupku.',
            'closing_message' => 'Masih banyak cerita yang menunggu kita.',
            'photos' => [],
            'vouchers' => ['Dinner pilihan kamu', 'Satu hari quality time', 'Jalan-jalan bersama'],
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDays(7)->subHour(),
            'is_enabled' => true,
        ];
    }
}
