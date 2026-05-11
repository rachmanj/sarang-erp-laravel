<?php

namespace Tests\Feature;

use App\Models\BusinessPartner;
use App\Models\CustomerApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerApiKeyAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_cannot_access_api_keys_page(): void
    {
        $customer = BusinessPartner::query()->customers()->active()->firstOrFail();

        $this->get(route('admin.customers.api-keys.index', $customer))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_api_keys_page(): void
    {
        $user = User::factory()->create();
        $customer = BusinessPartner::query()->customers()->active()->firstOrFail();

        $this->actingAs($user)
            ->get(route('admin.customers.api-keys.index', $customer))
            ->assertForbidden();
    }

    public function test_superadmin_can_create_and_revoke_api_key(): void
    {
        $admin = User::query()->firstOrFail();
        $this->assertTrue($admin->hasRole('superadmin'));

        $customer = BusinessPartner::query()->customers()->active()->firstOrFail();

        $response = $this->actingAs($admin)->get(route('admin.customers.api-keys.index', $customer));
        $response->assertOk();

        $post = $this->actingAs($admin)->post(route('admin.customers.api-keys.store', $customer), [
            'name' => 'Integration key',
        ]);

        $post->assertRedirect(route('admin.customers.api-keys.index', $customer));
        $post->assertSessionHas('new_api_token');

        $key = CustomerApiKey::query()->where('business_partner_id', $customer->id)->latest()->firstOrFail();

        $del = $this->actingAs($admin)->delete(route('admin.customers.api-keys.destroy', [$customer, $key]));
        $del->assertRedirect(route('admin.customers.api-keys.index', $customer));

        $this->assertDatabaseMissing('customer_api_keys', ['id' => $key->id]);
    }

    public function test_non_customer_partner_returns_404_on_api_keys_page(): void
    {
        $admin = User::query()->firstOrFail();
        $supplier = BusinessPartner::query()->suppliers()->active()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.customers.api-keys.index', $supplier))
            ->assertNotFound();
    }
}
