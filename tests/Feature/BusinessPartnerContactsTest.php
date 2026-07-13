<?php

namespace Tests\Feature;

use App\Models\BusinessPartner;
use App\Models\BusinessPartnerContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessPartnerContactsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::query()->firstOrFail();
    }

    public function test_business_partner_can_have_multiple_contacts_on_create(): void
    {
        $response = $this->actingAs($this->admin)->post(route('business_partners.store'), [
            'code' => 'CUST-MULTI-1',
            'name' => 'Multi Contact Customer',
            'partner_type' => 'customer',
            'status' => 'active',
            'contacts' => [
                [
                    'contact_type' => 'primary',
                    'name' => 'Alice Primary',
                    'email' => 'alice@example.com',
                    'phone' => '021-1111111',
                    'is_primary' => '1',
                ],
                [
                    'contact_type' => 'sales',
                    'name' => 'Bob Sales',
                    'email' => 'bob@example.com',
                    'mobile' => '0812-2222222',
                ],
                [
                    'contact_type' => 'billing',
                    'name' => 'Carol Billing',
                    'email' => 'carol@example.com',
                ],
            ],
        ]);

        $partner = BusinessPartner::query()->where('code', 'CUST-MULTI-1')->firstOrFail();

        $response->assertRedirect(route('business_partners.show', $partner));

        $this->assertSame(3, $partner->contacts()->count());
        $this->assertTrue($partner->primaryContact->is($partner->contacts()->where('name', 'Alice Primary')->first()));
        $this->assertSame('Bob Sales', $partner->contacts()->where('contact_type', 'sales')->first()->name);
    }

    public function test_update_replaces_contacts_with_multiple_new_ones(): void
    {
        $partner = BusinessPartner::create([
            'code' => 'CUST-MULTI-2',
            'name' => 'Update Contacts Customer',
            'partner_type' => 'customer',
        ]);

        BusinessPartnerContact::create([
            'business_partner_id' => $partner->id,
            'contact_type' => 'primary',
            'name' => 'Old Contact',
            'is_primary' => true,
        ]);

        $response = $this->actingAs($this->admin)->put(route('business_partners.update', $partner), [
            'code' => $partner->code,
            'name' => $partner->name,
            'partner_type' => 'customer',
            'status' => 'active',
            'contacts' => [
                [
                    'contact_type' => 'technical',
                    'name' => 'Tech Lead',
                    'email' => 'tech@example.com',
                ],
                [
                    'contact_type' => 'support',
                    'name' => 'Support Desk',
                    'phone' => '021-3333333',
                    'is_primary' => '1',
                ],
            ],
        ]);

        $partner->refresh();

        $response->assertRedirect(route('business_partners.show', $partner));
        $this->assertSame(2, $partner->contacts()->count());
        $this->assertNull($partner->contacts()->where('name', 'Old Contact')->first());
        $this->assertSame('Support Desk', $partner->primaryContact->name);
    }

    public function test_service_ensures_exactly_one_primary_contact_when_none_marked(): void
    {
        $partner = BusinessPartner::create([
            'code' => 'CUST-MULTI-3',
            'name' => 'Primary Fallback Customer',
            'partner_type' => 'customer',
        ]);

        $this->actingAs($this->admin)->put(route('business_partners.update', $partner), [
            'code' => $partner->code,
            'name' => $partner->name,
            'partner_type' => 'customer',
            'status' => 'active',
            'contacts' => [
                [
                    'contact_type' => 'sales',
                    'name' => 'First Contact',
                ],
                [
                    'contact_type' => 'billing',
                    'name' => 'Second Contact',
                ],
            ],
        ]);

        $partner->refresh();

        $this->assertSame(1, $partner->contacts()->where('is_primary', true)->count());
        $this->assertSame('First Contact', $partner->primaryContact->name);
    }

    public function test_contacts_endpoint_returns_all_partner_contacts(): void
    {
        $partner = BusinessPartner::create([
            'code' => 'CUST-MULTI-4',
            'name' => 'Contacts API Customer',
            'partner_type' => 'customer',
        ]);

        BusinessPartnerContact::create([
            'business_partner_id' => $partner->id,
            'contact_type' => 'primary',
            'name' => 'Primary Person',
            'is_primary' => true,
        ]);

        BusinessPartnerContact::create([
            'business_partner_id' => $partner->id,
            'contact_type' => 'sales',
            'name' => 'Sales Person',
            'mobile' => '0812-4444444',
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('business_partners.contacts', $partner));

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['name' => 'Primary Person', 'is_primary' => true])
            ->assertJsonFragment(['name' => 'Sales Person', 'display_phone' => '0812-4444444']);
    }
}
