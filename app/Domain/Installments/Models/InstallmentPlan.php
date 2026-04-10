<?php

namespace App\Domain\Installments\Models;

use App\Domain\CreditCards\Models\CreditCard;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentPlan extends Model
{
    protected $fillable = [
        'user_id',
        'credit_card_id',
        'description',
        'total_amount',
        'installment_amount',
        'total_installments',
        'paid_installments',
        'starts_at',
        'cancelled_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'starts_at' => 'date',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creditCard(): BelongsTo
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->paid_installments >= $this->total_installments;
    }

    public function getIsActiveAttribute(): bool
    {
        return is_null($this->cancelled_at) && ! $this->is_completed;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('cancelled_at')
            ->whereColumn('paid_installments', '<', 'total_installments');
    }
}
