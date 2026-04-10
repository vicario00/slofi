<?php

namespace App\Domain\Transactions\Actions;

use App\Domain\Accounts\Models\Account;
use App\Domain\Categories\Models\Category;
use App\Domain\CreditCards\Models\CreditCard;
use App\Domain\Tags\Services\TaggingService;
use App\Domain\Transactions\DTOs\RegisterTransactionData;
use App\Domain\Transactions\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RegisterTransactionAction
{
    public function __construct(
        private readonly TaggingService $taggingService,
    ) {}

    public function execute(User $user, RegisterTransactionData $data): Transaction
    {
        // 1. Resolve and authorize payable
        $payable = $this->resolvePayable($user, $data->payable_type, $data->payable_id);

        return DB::transaction(function () use ($user, $data, $payable) {
            // 2. Resolve category_id: use provided or fall back to 'others'
            $categoryId = $data->category_id
                ?? Category::whereNull('user_id')->where('slug', 'others')->value('id');

            // 3. Create the primary transaction
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'payable_type' => get_class($payable),
                'payable_id' => $payable->id,
                'amount' => $data->amount,
                'type' => $data->type,
                'description' => $data->description,
                'merchant' => $data->merchant,
                'transacted_at' => $data->transacted_at,
                'notes' => $data->notes,
                'category_id' => $categoryId,
            ]);

            // 4. Handle transfer: create paired transaction
            if ($data->type === 'transfer') {
                $targetPayable = $this->resolvePayable($user, $data->payable_type, $data->target_payable_id);

                $paired = Transaction::create([
                    'user_id' => $user->id,
                    'payable_type' => get_class($targetPayable),
                    'payable_id' => $targetPayable->id,
                    'amount' => $data->amount,
                    'type' => 'transfer',
                    'description' => $data->description,
                    'merchant' => $data->merchant,
                    'transacted_at' => $data->transacted_at,
                    'notes' => $data->notes,
                    'transfer_pair_id' => $transaction->id,
                    'category_id' => $categoryId,
                ]);

                // Link the original transaction back to the pair
                $transaction->update(['transfer_pair_id' => $paired->id]);
            }

            // 5. Auto-tag
            $this->taggingService->assign($transaction);

            // 6. Return with tags and category loaded
            return $transaction->load(['tags', 'category']);
        });
    }

    private function resolvePayable(User $user, string $type, ?int $id): Account|CreditCard
    {
        if ($id === null) {
            throw new \InvalidArgumentException("Payable ID required for type: {$type}");
        }

        $payable = match ($type) {
            'account' => Account::find($id),
            'credit_card' => CreditCard::find($id),
            default => throw new \InvalidArgumentException("Invalid payable type: {$type}"),
        };

        if (! $payable || $payable->user_id !== $user->id) {
            throw new HttpException(403, 'This resource does not belong to you.');
        }

        return $payable;
    }
}
