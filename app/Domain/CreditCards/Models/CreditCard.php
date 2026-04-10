<?php

namespace App\Domain\CreditCards\Models;

use App\Domain\CreditCards\Services\BillingCycleService;
use App\Domain\Transactions\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CreditCard extends Model
{
    protected $fillable = [
        'user_id', 'name', 'last_four', 'cutoff_day', 'payment_grace_days',
        'credit_limit', 'currency', 'color',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'cutoff_day' => 'integer',
        'payment_grace_days' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'payable');
    }

    protected function paymentDueDate(): Attribute
    {
        return Attribute::make(
            get: fn () => app(BillingCycleService::class)->lastCutoffDate($this)->addDays($this->payment_grace_days)
        );
    }
}
