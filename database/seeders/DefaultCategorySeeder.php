<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class DefaultCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Expense Categories
            ['name' => 'Makan & Kencan', 'type' => 'expense', 'icon' => 'utensils', 'color' => '#F43F5E', 'is_default' => true],
            ['name' => 'Kopi & Nongkrong', 'type' => 'expense', 'icon' => 'coffee', 'color' => '#8B5CF6', 'is_default' => true],
            ['name' => 'Nonton & Hiburan', 'type' => 'expense', 'icon' => 'film', 'color' => '#EC4899', 'is_default' => true],
            ['name' => 'Belanja & Groceries', 'type' => 'expense', 'icon' => 'shopping-cart', 'color' => '#3B82F6', 'is_default' => true],
            ['name' => 'Bensin & Transport', 'type' => 'expense', 'icon' => 'car', 'color' => '#F59E0B', 'is_default' => true],
            ['name' => 'Tagihan & Wi-Fi', 'type' => 'expense', 'icon' => 'receipt', 'color' => '#10B981', 'is_default' => true],
            ['name' => 'Kado & Surprise', 'type' => 'expense', 'icon' => 'gift', 'color' => '#EF4444', 'is_default' => true],
            ['name' => 'Kesehatan & Skincare', 'type' => 'expense', 'icon' => 'heart-pulse', 'color' => '#14B8A6', 'is_default' => true],
            ['name' => 'Liburan & Trip', 'type' => 'expense', 'icon' => 'plane', 'color' => '#06B6D4', 'is_default' => true],
            ['name' => 'Lain-lain', 'type' => 'expense', 'icon' => 'circle-ellipsis', 'color' => '#64748B', 'is_default' => true],

            // Income Categories
            ['name' => 'Gaji Pokok', 'type' => 'income', 'icon' => 'banknote', 'color' => '#10B981', 'is_default' => true],
            ['name' => 'Bonus & THR', 'type' => 'income', 'icon' => 'sparkles', 'color' => '#F59E0B', 'is_default' => true],
            ['name' => 'Side Hustle / Freelance', 'type' => 'income', 'icon' => 'laptop', 'color' => '#6366F1', 'is_default' => true],
            ['name' => 'Investasi & Dividen', 'type' => 'income', 'icon' => 'trending-up', 'color' => '#3B82F6', 'is_default' => true],
            ['name' => 'Pemberian / Hadiah', 'type' => 'income', 'icon' => 'gift', 'color' => '#EC4899', 'is_default' => true],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['name' => $cat['name'], 'type' => $cat['type'], 'couple_space_id' => null],
                $cat
            );
        }
    }
}
