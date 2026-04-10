<?php

namespace Database\Seeders;

use App\Domain\Categories\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public const SYSTEM_SLUGS = [
        'food_dining',
        'transport',
        'entertainment',
        'health',
        'shopping',
        'home',
        'education',
        'travel',
        'subscriptions',
        'personal_care',
        'pets',
        'sports_fitness',
        'gifts_donations',
        'taxes_fees',
        'salary',
        'freelance',
        'investments',
        'rental_income',
        'refunds',
        'gifts_received',
        'others',
    ];

    public function run(): void
    {
        foreach (self::SYSTEM_SLUGS as $slug) {
            Category::updateOrCreate(
                ['slug' => $slug],
                ['user_id' => null],
            );
        }
    }
}
