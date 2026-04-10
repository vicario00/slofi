<?php

namespace App\Domain\Categories\DTOs;

use Spatie\LaravelData\Data;

class CreateCategoryData extends Data
{
    public function __construct(
        public readonly string $slug,
        public readonly int $user_id,
    ) {}

    public static function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', 'unique:categories,slug'],
            'user_id' => ['required', 'integer'],
        ];
    }
}
