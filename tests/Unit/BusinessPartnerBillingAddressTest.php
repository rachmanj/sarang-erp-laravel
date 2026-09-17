<?php

namespace Tests\Unit;

use App\Models\BusinessPartner;
use App\Models\BusinessPartnerAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessPartnerBillingAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_address_prefers_primary_billing_even_when_created_last(): void
    {
        $partner = BusinessPartner::create([
            'code' => 'CUST-BILL-1',
            'name' => 'Primary Billing Last Customer',
            'partner_type' => 'customer',
        ]);

        $nonPrimary = BusinessPartnerAddress::create([
            'business_partner_id' => $partner->id,
            'address_type' => 'billing',
            'address_line_1' => 'Jl. Raya Wantilan-Cipeundeuy No. 43',
            'city' => 'Subang',
            'is_primary' => false,
        ]);

        $primary = BusinessPartnerAddress::create([
            'business_partner_id' => $partner->id,
            'address_type' => 'billing',
            'address_line_1' => 'Jl. Surapati No. 5',
            'city' => 'Bandung',
            'postal_code' => '40132',
            'state_province' => 'West Java',
            'country' => 'Indonesia',
            'is_primary' => true,
        ]);

        $partner->refresh();

        $this->assertTrue($primary->id > $nonPrimary->id);
        $this->assertTrue($partner->billingAddress->is($primary));
        $this->assertNotTrue($partner->billingAddress->is($nonPrimary));
        $this->assertStringContainsString('Jl. Surapati No. 5', $partner->default_billing_address);
        $this->assertStringNotContainsString('Wantilan-Cipeundeuy', $partner->default_billing_address);
    }

    public function test_billing_address_falls_back_to_first_billing_when_no_primary_billing(): void
    {
        $partner = BusinessPartner::create([
            'code' => 'CUST-BILL-2',
            'name' => 'No Primary Billing Customer',
            'partner_type' => 'customer',
        ]);

        $first = BusinessPartnerAddress::create([
            'business_partner_id' => $partner->id,
            'address_type' => 'billing',
            'address_line_1' => 'First Billing Street',
            'city' => 'Jakarta',
            'is_primary' => false,
        ]);

        BusinessPartnerAddress::create([
            'business_partner_id' => $partner->id,
            'address_type' => 'billing',
            'address_line_1' => 'Second Billing Street',
            'city' => 'Bandung',
            'is_primary' => false,
        ]);

        $partner->refresh();

        $this->assertTrue($partner->billingAddress->is($first));
        $this->assertStringContainsString('First Billing Street', $partner->default_billing_address);
    }

    public function test_billing_address_falls_back_to_primary_address_when_no_billing_rows(): void
    {
        $partner = BusinessPartner::create([
            'code' => 'CUST-BILL-3',
            'name' => 'No Billing Customer',
            'partner_type' => 'customer',
        ]);

        $shippingPrimary = BusinessPartnerAddress::create([
            'business_partner_id' => $partner->id,
            'address_type' => 'shipping',
            'address_line_1' => 'Shipping Primary Address',
            'city' => 'Surabaya',
            'is_primary' => true,
        ]);

        $partner->refresh();

        $this->assertTrue($partner->billingAddress->is($shippingPrimary));
        $this->assertStringContainsString('Shipping Primary Address', $partner->default_billing_address);
    }

    public function test_billing_address_returns_null_when_partner_has_no_addresses(): void
    {
        $partner = BusinessPartner::create([
            'code' => 'CUST-BILL-4',
            'name' => 'No Address Customer',
            'partner_type' => 'customer',
        ]);

        $partner->refresh();

        $this->assertNull($partner->billingAddress);
        $this->assertNull($partner->default_billing_address);
    }
}
