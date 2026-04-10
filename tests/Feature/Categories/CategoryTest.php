<?php

namespace Tests\Feature\Categories;

use App\Domain\Accounts\Models\Account;
use App\Domain\Categories\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_system_and_user_categories(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->seed(CategorySeeder::class);

        // Create one user custom category
        Category::create(['slug' => 'my_custom', 'user_id' => $user->id]);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Should include system categories + custom one
        $slugs = array_column($data, 'slug');
        $this->assertContains('food_dining', $slugs);
        $this->assertContains('others', $slugs);
        $this->assertContains('my_custom', $slugs);

        // System categories have is_system = true
        $systemItem = collect($data)->firstWhere('slug', 'food_dining');
        $this->assertTrue($systemItem['is_system']);

        // User custom has is_system = false
        $customItem = collect($data)->firstWhere('slug', 'my_custom');
        $this->assertFalse($customItem['is_system']);
    }

    public function test_can_create_custom_category(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/categories', [
            'slug' => 'my_category',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['slug' => 'my_category', 'is_system' => false]);

        $this->assertDatabaseHas('categories', [
            'slug' => 'my_category',
            'user_id' => $user->id,
        ]);
    }

    public function test_slug_must_be_lowercase_alphanumeric_underscore(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/categories', [
            'slug' => 'Invalid-Slug!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_duplicate_slug_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Category::create(['slug' => 'my_category', 'user_id' => $user->id]);

        $response = $this->postJson('/api/categories', [
            'slug' => 'my_category',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_can_delete_own_custom_category(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $category = Category::create(['slug' => 'to_delete', 'user_id' => $user->id]);

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_cannot_delete_system_category(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->seed(CategorySeeder::class);

        $systemCategory = Category::whereNull('user_id')->where('slug', 'food_dining')->first();

        $response = $this->deleteJson("/api/categories/{$systemCategory->id}");

        $response->assertStatus(403);
    }

    public function test_cannot_delete_other_users_category(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $category = Category::create(['slug' => 'owners_category', 'user_id' => $owner->id]);

        Sanctum::actingAs($intruder);

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(403);
    }

    public function test_locale_param_changes_name_translation(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->seed(CategorySeeder::class);

        $responseEn = $this->getJson('/api/categories?locale=en');
        $responseEn->assertStatus(200);
        $enItem = collect($responseEn->json('data'))->firstWhere('slug', 'food_dining');
        $this->assertEquals('Food & Dining', $enItem['name']);

        $responseEs = $this->getJson('/api/categories?locale=es');
        $responseEs->assertStatus(200);
        $esItem = collect($responseEs->json('data'))->firstWhere('slug', 'food_dining');
        $this->assertEquals('Comida y restaurantes', $esItem['name']);
    }

    public function test_transaction_gets_others_category_when_none_provided(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->seed(CategorySeeder::class);

        $account = Account::create([
            'user_id' => $user->id,
            'name' => 'Main Account',
            'type' => 'checking',
            'balance' => '5000.00',
            'currency' => 'MXN',
        ]);

        $response = $this->postJson('/api/transactions', [
            'payable_type' => 'account',
            'payable_id' => $account->id,
            'amount' => 100.00,
            'type' => 'expense',
            'description' => 'No category provided',
            'transacted_at' => now()->toDateString(),
        ]);

        $response->assertStatus(201);

        $categoryData = $response->json('data.category');
        $this->assertNotNull($categoryData, 'Transaction should have a category assigned');
        $this->assertEquals('others', $categoryData['slug']);
    }
}
