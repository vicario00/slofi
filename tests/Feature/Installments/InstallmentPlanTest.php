<?php

namespace Tests\Feature\Installments;

use App\Domain\CreditCards\Models\CreditCard;
use App\Domain\Installments\Models\InstallmentPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InstallmentPlanTest extends TestCase
{
    use RefreshDatabase;

    private function createCreditCard(User $user, array $overrides = []): CreditCard
    {
        return CreditCard::create(array_merge([
            'user_id' => $user->id,
            'name' => 'My Visa',
            'last_four' => '1234',
            'cutoff_day' => 15,
            'payment_grace_days' => 10,
            'credit_limit' => 10000.00,
            'currency' => 'MXN',
            'color' => '#0000FF',
        ], $overrides));
    }

    private function createPlan(User $user, CreditCard $card, array $overrides = []): InstallmentPlan
    {
        return InstallmentPlan::create(array_merge([
            'user_id' => $user->id,
            'credit_card_id' => $card->id,
            'description' => 'Laptop',
            'total_amount' => '1200.00',
            'installment_amount' => '400.00',
            'total_installments' => 3,
            'paid_installments' => 0,
            'starts_at' => now()->toDateString(),
        ], $overrides));
    }

    public function test_can_create_plan_with_divisible_amount(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $card = $this->createCreditCard($user);

        $response = $this->postJson('/api/installment-plans', [
            'description' => 'Laptop',
            'total_amount' => 1200.00,
            'total_installments' => 3,
            'credit_card_id' => $card->id,
            'starts_at' => now()->toDateString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['description' => 'Laptop'])
            ->assertJsonFragment(['total_installments' => 3]);

        $this->assertDatabaseHas('installment_plans', [
            'description' => 'Laptop',
            'user_id' => $user->id,
            'installment_amount' => '400.00',
        ]);
    }

    public function test_can_create_plan_with_explicit_installment_amount(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $card = $this->createCreditCard($user);

        $response = $this->postJson('/api/installment-plans', [
            'description' => 'Phone',
            'total_amount' => 1300.00,
            'total_installments' => 3,
            'credit_card_id' => $card->id,
            'starts_at' => now()->toDateString(),
            'installment_amount' => 433.34,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['description' => 'Phone']);

        $this->assertDatabaseHas('installment_plans', [
            'description' => 'Phone',
            'installment_amount' => '433.34',
        ]);
    }

    public function test_returns_422_when_amount_not_divisible_and_no_installment_amount(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $card = $this->createCreditCard($user);

        $response = $this->postJson('/api/installment-plans', [
            'description' => 'Phone',
            'total_amount' => 1300.00,
            'total_installments' => 3,
            'credit_card_id' => $card->id,
            'starts_at' => now()->toDateString(),
            // no installment_amount
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'installment_amount is required when total_amount is not evenly divisible']);
    }

    public function test_can_cancel_plan(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $card = $this->createCreditCard($user);
        $plan = $this->createPlan($user, $card);

        $response = $this->deleteJson("/api/installment-plans/{$plan->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        // Not deleted from DB, cancelled_at set
        $this->assertDatabaseHas('installment_plans', ['id' => $plan->id]);
        $this->assertNotNull(InstallmentPlan::find($plan->id)->cancelled_at);
    }

    public function test_cannot_access_other_users_plan(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $card = $this->createCreditCard($owner);
        $plan = $this->createPlan($owner, $card);

        Sanctum::actingAs($intruder);

        $response = $this->getJson("/api/installment-plans/{$plan->id}");

        $response->assertStatus(403);
    }

    public function test_min_2_installments_required(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $card = $this->createCreditCard($user);

        $response = $this->postJson('/api/installment-plans', [
            'description' => 'One time',
            'total_amount' => 500.00,
            'total_installments' => 1,
            'credit_card_id' => $card->id,
            'starts_at' => now()->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['total_installments']);
    }
}
