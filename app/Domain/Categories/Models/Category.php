<?php

namespace App\Domain\Categories\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['slug', 'user_id'];

    public function getNameAttribute(): string
    {
        return __('categories.'.$this->slug, [], app()->getLocale())
            ?: $this->slug;
    }
}
