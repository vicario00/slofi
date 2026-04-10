<?php

namespace Tests\Feature\CreditCards;

use App\Domain\CreditCards\Models\CreditCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreditCardTest extends TestCase
{
    use RefreshDatabase;

    private function createCard(User $user, array $overrides = []): CreditCard
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

    public function test_can_create_credit_card(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/credit-cards', [
            'name' => 'My Mastercard',
            'last_four' => '4321',
            'cutoff_day' => 20,
            'payment_grace_days' => 15,
            'credit_limit' => 5000.00,
            'currency' => 'MXN',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'My Mastercard']);

        $this->assertDatabaseHas('credit_cards', ['name' => 'My Mastercard', 'user_id' => $user->id]);
    }

    public function test_can_list_credit_cards(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->createCard($user, ['name' => 'Card A']);
        $this->createCard($user, ['name' => 'Card B']);

        $response = $this->getJson('/api/credit-cards');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_get_balance(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $card = $this->createCard($user);

        $response = $this->getJson("/api/credit-cards/{$card->id}/balance");

        $response->assertStatus(200)
            ->assertJsonStructure(['period_start', 'period_end', 'balance']);
    }

    public function test_cutoff_day_max_28_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/credit-cards', [
            'name' => 'Bad Card',
            'last_four' => '9999',
            'cutoff_day' => 31,
            'payment_grace_days' => 15,
            'credit_limit' => 1000.00,
            'currency' => 'MXN',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cutoff_day']);
    }

    public function test_cannot_access_other_users_card(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $card = $this->createCard($owner);

        Sanctum::actingAs($intruder);

        $response = $this->getJson("/api/credit-cards/{$card->id}");

        $response->assertStatus(403);
    }
}
