<?php

namespace App\Domain\Categories\Actions;

use App\Domain\Categories\DTOs\CreateCategoryData;
use App\Domain\Categories\Models\Category;

class CreateCategoryAction
{
    public function execute(CreateCategoryData $data): Category
    {
        return Category::create([
            'slug' => $data->slug,
            'user_id' => $data->user_id,
        ]);
    }
}
