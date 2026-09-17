<?php

namespace Tests\Feature;

use App\Models\Accounting\SalesInvoice;
use App\Models\BusinessPartner;
use App\Models\BusinessPartnerAddress;
use App\Models\User;
use App\Services\Accounting\SalesInvoicePostingMath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesInvoiceBillToAddressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $user = User::factory()->create();
        $this->actingAs($user);
    }

    /**
     * @return array{invoice: SalesInvoice, primaryLine: string, nonPrimaryLine: string}
     */
    private function createInvoiceWithMultipleBillingAddresses(): array
    {
        $partner = BusinessPartner::create([
            'code' => 'CUST-SI-BILLTO',
            'name' => 'DIRGANTARA YUDHA ARTHA, PT',
            'partner_type' => 'customer',
            'tax_id' => '01.234.567.8-901.000',
        ]);

        $nonPrimaryLine = 'Jl. Raya Wantilan-Cipeundeuy No. 43';
        BusinessPartnerAddress::create([
            'business_partner_id' => $partner->id,
            'address_type' => 'billing',
            'address_line_1' => $nonPrimaryLine,
            'city' => 'Subang',
            'is_primary' => false,
        ]);

        BusinessPartnerAddress::create([
            'business_partner_id' => $partner->id,
            'address_type' => 'billing',
            'address_line_1' => 'Jl. Jambi Secondary',
            'city' => 'Jambi',
            'is_primary' => false,
        ]);

        $primaryLine = 'Jl. Surapati No. 5';
        BusinessPartnerAddress::create([
            'business_partner_id' => $partner->id,
            'address_type' => 'billing',
            'address_line_1' => $primaryLine,
            'city' => 'Bandung',
            'postal_code' => '40132',
            'state_province' => 'West Java',
            'country' => 'Indonesia',
            'is_primary' => true,
        ]);

        $entityId = (int) DB::table('company_entities')->value('id');
        $currencyId = (int) DB::table('currencies')->value('id');

        $invoice = SalesInvoice::query()->create([
            'invoice_no' => 'T-SI-BILLTO-'.uniqid(),
            'date' => now()->toDateString(),
            'business_partner_id' => $partner->id,
            'company_entity_id' => $entityId,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'total_amount' => 100000,
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        $invoice->load([
            'businessPartner.primaryAddress',
            'businessPartner.addresses',
            'lines',
            'deliveryOrders',
            'companyEntity',
        ]);

        return [
            'invoice' => $invoice,
            'primaryLine' => $primaryLine,
            'nonPrimaryLine' => $nonPrimaryLine,
        ];
    }

    public function test_show_view_bill_to_uses_primary_billing_address(): void
    {
        ['invoice' => $invoice, 'primaryLine' => $primaryLine, 'nonPrimaryLine' => $nonPrimaryLine] = $this->createInvoiceWithMultipleBillingAddresses();

        $html = view('sales_invoices.show', [
            'invoice' => $invoice,
            'canCreateCreditMemo' => false,
            'hasCreditMemo' => false,
            'invoiceFooter' => SalesInvoicePostingMath::invoiceFooterTotals($invoice),
            'totalAllocated' => 0,
            'remainingBalance' => 0,
            'canCreateReceipt' => false,
        ])->render();

        $this->assertStringContainsString('Bill To', $html);
        $this->assertStringContainsString($primaryLine, $html);
        $this->assertStringNotContainsString($nonPrimaryLine, $html);
    }

    public function test_print_views_bill_to_uses_primary_billing_address(): void
    {
        ['invoice' => $invoice, 'primaryLine' => $primaryLine, 'nonPrimaryLine' => $nonPrimaryLine] = $this->createInvoiceWithMultipleBillingAddresses();

        $invoiceFooter = SalesInvoicePostingMath::invoiceFooterTotals($invoice);
        $viewData = compact('invoice', 'invoiceFooter') + ['entity' => null];

        foreach (['sales_invoices.print', 'sales_invoices.print_pt_csj', 'sales_invoices.print_cv_saranghae'] as $viewName) {
            $html = view($viewName, $viewData)->render();

            $this->assertStringContainsString('Bill To', $html, "View {$viewName} should render Bill To block.");
            $this->assertStringContainsString($primaryLine, $html, "View {$viewName} should show primary billing address.");
            $this->assertStringNotContainsString($nonPrimaryLine, $html, "View {$viewName} should not show non-primary billing address.");
        }
    }
}
