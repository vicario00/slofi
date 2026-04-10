<?php

namespace App\Domain\Transactions\Models;

use App\Domain\Categories\Models\Category;
use App\Domain\Tags\Models\Tag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'payable_type',
        'payable_id',
        'amount',
        'type',
        'description',
        'merchant',
        'transacted_at',
        'notes',
        'transfer_pair_id',
        'category_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transacted_at' => 'date',
    ];

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'transaction_tags');
    }

    public function transferPair(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transfer_pair_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
