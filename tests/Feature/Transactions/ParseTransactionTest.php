<?php

namespace Tests\Feature\Transactions;

use App\Domain\CreditCards\Models\CreditCard;
use App\Domain\Transactions\DTOs\ParsedTransactionDraft;
use App\Domain\Transactions\Services\FakeParserService;
use App\Domain\Transactions\Services\ParserServiceInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ParseTransactionTest extends TestCase
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
        ], $overrides));
    }

    public function test_successful_parse_returns_draft_200(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $draft = ParsedTransactionDraft::from([
            'amount' => 150.0,
            'type' => 'expense',
            'merchant' => 'Supermercado',
            'description' => 'Gasté 150 en el super',
            'transacted_at' => '2026-04-13',
            'notes' => null,
            'suggested_category_slug' => 'groceries',
            'suggested_tags' => ['food'],
            'confidence' => 0.88,
            'requires_confirmation' => false,
            'inferred_payable_type' => null,
            'inferred_payable_id' => null,
        ]);

        app()->instance(ParserServiceInterface::class, FakeParserService::returning($draft));

        $response = $this->postJson('/api/transactions/parse', [
            'raw_text' => 'Gasté 150 en el super',
            'context' => [],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.amount', 150)
            ->assertJsonPath('data.confidence', 0.88)
            ->assertJsonPath('data.requires_confirmation', false);
    }

    public function test_low_confidence_returns_draft_with_requires_confirmation_true(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $draft = ParsedTransactionDraft::from([
            'amount' => 300.0,
            'type' => 'expense',
            'merchant' => null,
            'description' => 'Compra no reconocida',
            'transacted_at' => '2026-04-13',
            'notes' => null,
            'suggested_category_slug' => null,
            'suggested_tags' => [],
            'confidence' => 0.4,
            'requires_confirmation' => true,
            'inferred_payable_type' => null,
            'inferred_payable_id' => null,
        ]);

        app()->instance(ParserServiceInterface::class, FakeParserService::returning($draft));

        $response = $this->postJson('/api/transactions/parse', [
            'raw_text' => 'Algo que no entiendo bien',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.requires_confirmation', true)
            ->assertJsonPath('data.confidence', 0.4);
    }

    public function test_inferred_payable_included_in_draft(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $card = $this->createCreditCard($user, ['name' => 'Visa nueva']);

        $draft = ParsedTransactionDraft::from([
            'amount' => 500.0,
            'type' => 'expense',
            'merchant' => null,
            'description' => 'Compré unos guantes',
            'transacted_at' => '2026-04-13',
            'notes' => null,
            'suggested_category_slug' => 'shopping',
            'suggested_tags' => ['clothing'],
            'confidence' => 0.82,
            'requires_confirmation' => false,
            'inferred_payable_type' => 'credit_card',
            'inferred_payable_id' => $card->id,
        ]);

        app()->instance(ParserServiceInterface::class, FakeParserService::returning($draft));

        $response = $this->postJson('/api/transactions/parse', [
            'raw_text' => 'Compré unos guantes de 500 pesos con Visa nueva',
            'context' => [
                'credit_cards' => [['id' => $card->id, 'name' => 'Visa nueva']],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.inferred_payable_type', 'credit_card')
            ->assertJsonPath('data.inferred_payable_id', $card->id);
    }

    public function test_parser_unavailable_returns_503(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        app()->instance(ParserServiceInterface::class, FakeParserService::unavailable());

        $response = $this->postJson('/api/transactions/parse', [
            'raw_text' => 'Gasté 200 en el uber',
        ]);

        $response->assertStatus(503)
            ->assertJsonPath('message', 'Parser service unavailable.');
    }

    public function test_unauthenticated_returns_401(): void
    {
        $response = $this->postJson('/api/transactions/parse', [
            'raw_text' => 'Gasté 200 en el uber',
        ]);

        $response->assertStatus(401);
    }

    public function test_missing_raw_text_returns_422(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        app()->instance(ParserServiceInterface::class, FakeParserService::unavailable());

        $response = $this->postJson('/api/transactions/parse', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['raw_text']);
    }
}
