<?php

namespace Tests\Feature;

use App\Models\BusinessPartner;
use App\Models\BusinessPartnerAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessPartnerAddressResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_office_address_prefers_office_type_over_registered_billing_and_primary(): void
    {
        $partner = BusinessPartner::create([
            'code' => 'CUST-OFFICE-1',
            'name' => 'Office Fallback Customer',
            'partner_type' => 'customer',
        ]);

        $billing = BusinessPartnerAddress::create([
            'business_partner_id' => $partner->id,
            'address_type' => 'billing',
            'address_line_1' => 'Billing Street 1',
            'city' => 'Jakarta',
            'is_primary' => true,
        ]);

        $office = BusinessPartnerAddress::create([
            'business_partner_id' => $partner->id,
            'address_type' => 'office',
            'address_line_1' => 'Office Tower Floor 5',
            'city' => 'Jakarta',
            'is_primary' => false,
        ]);

        $partner->refresh();

        $this->assertTrue($partner->officeAddress->is($office));
        $this->assertNotTrue($partner->officeAddress->is($billing));
        $this->assertStringContainsString('Office Tower Floor 5', $partner->default_office_address);
    }

    public function test_office_address_falls_back_to_registered_then_billing_then_primary(): void
    {
        $partner = BusinessPartner::create([
            'code' => 'CUST-OFFICE-2',
            'name' => 'No Office Address Customer',
            'partner_type' => 'customer',
        ]);

        $registered = BusinessPartnerAddress::create([
            'business_partner_id' => $partner->id,
            'address_type' => 'registered',
            'address_line_1' => 'Registered HQ',
            'city' => 'Bandung',
            'is_primary' => false,
        ]);

        $partner->refresh();

        $this->assertTrue($partner->officeAddress->is($registered));

        $registered->delete();
        $partner->refresh();

        $billing = BusinessPartnerAddress::create([
            'business_partner_id' => $partner->id,
            'address_type' => 'billing',
            'address_line_1' => 'Billing Address',
            'city' => 'Bandung',
            'is_primary' => true,
        ]);

        $partner->refresh();
        $this->assertTrue($partner->officeAddress->is($billing));
    }

    public function test_warehouse_address_prefers_warehouse_type_over_shipping_and_primary(): void
    {
        $partner = BusinessPartner::create([
            'code' => 'CUST-WH-1',
            'name' => 'Warehouse Fallback Customer',
            'partner_type' => 'customer',
        ]);

        $shipping = BusinessPartnerAddress::create([
            'business_partner_id' => $partner->id,
            'address_type' => 'shipping',
            'address_line_1' => 'Shipping Dock 1',
            'city' => 'Surabaya',
            'is_primary' => true,
        ]);

        $warehouse = BusinessPartnerAddress::create([
            'business_partner_id' => $partner->id,
            'address_type' => 'warehouse',
            'address_line_1' => 'Warehouse Gudang 2',
            'city' => 'Surabaya',
            'is_primary' => false,
        ]);

        $partner->refresh();

        $this->assertTrue($partner->warehouseAddress->is($warehouse));
        $this->assertNotTrue($partner->warehouseAddress->is($shipping));
        $this->assertStringContainsString('Warehouse Gudang 2', $partner->default_warehouse_address);
    }

    public function test_warehouse_address_falls_back_to_shipping_then_primary(): void
    {
        $partner = BusinessPartner::create([
            'code' => 'CUST-WH-2',
            'name' => 'No Warehouse Address Customer',
            'partner_type' => 'customer',
        ]);

        $shipping = BusinessPartnerAddress::create([
            'business_partner_id' => $partner->id,
            'address_type' => 'shipping',
            'address_line_1' => 'Shipping Only Address',
            'city' => 'Medan',
            'is_primary' => true,
        ]);

        $partner->refresh();

        $this->assertTrue($partner->warehouseAddress->is($shipping));
    }

    public function test_office_and_warehouse_addresses_resolve_independently_for_same_customer(): void
    {
        $partner = BusinessPartner::create([
            'code' => 'CUST-BOTH-1',
            'name' => 'Office And Warehouse Customer',
            'partner_type' => 'customer',
        ]);

        BusinessPartnerAddress::create([
            'business_partner_id' => $partner->id,
            'address_type' => 'office',
            'address_line_1' => 'Head Office Address',
            'city' => 'Jakarta',
            'is_primary' => true,
        ]);

        BusinessPartnerAddress::create([
            'business_partner_id' => $partner->id,
            'address_type' => 'warehouse',
            'address_line_1' => 'Distribution Warehouse Address',
            'city' => 'Bekasi',
            'is_primary' => false,
        ]);

        $partner->refresh();

        $this->assertStringContainsString('Head Office Address', $partner->default_office_address);
        $this->assertStringContainsString('Distribution Warehouse Address', $partner->default_warehouse_address);
        $this->assertNotSame($partner->default_office_address, $partner->default_warehouse_address);
    }

    public function test_business_partner_address_can_store_phone_number(): void
    {
        $partner = BusinessPartner::create([
            'code' => 'CUST-ADDR-PHONE',
            'name' => 'Address Phone Customer',
            'partner_type' => 'customer',
        ]);

        $address = BusinessPartnerAddress::create([
            'business_partner_id' => $partner->id,
            'address_type' => 'office',
            'address_line_1' => 'Jl. Phone Test 1',
            'city' => 'Jakarta',
            'country' => 'Indonesia',
            'phone' => '021-5559999',
            'is_primary' => true,
        ]);

        $this->assertSame('021-5559999', $address->refresh()->phone);
    }
}
