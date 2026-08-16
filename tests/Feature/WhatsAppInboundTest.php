<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderApproval;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppInboundHandler;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WhatsAppInboundTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('whatsapp.module_enabled', true);
        Config::set('whatsapp.expiry', null);
        Config::set('whatsapp.api_key', 'test-token');
        Config::set('whatsapp.sender_number', '628111111111');
        Config::set('whatsapp.owner_phone', '628222222222');

        $this->ensureWhatsAppSchema();

        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true, 'id' => 'fake-fonnte-id'], 200),
        ]);
    }

    protected function ensureWhatsAppSchema(): void
    {
        if (! Schema::hasTable('whatsapp_messages')) {
            Schema::create('whatsapp_messages', function (Blueprint $table) {
                $table->id();
                $table->enum('direction', ['in', 'out']);
                $table->string('gateway_message_id')->nullable()->unique();
                $table->string('to_number');
                $table->string('from_number');
                $table->text('body');
                $table->string('message_type')->default('text');
                $table->string('status')->default('pending');
                $table->string('related_entity_type')->nullable();
                $table->unsignedBigInteger('related_entity_id')->nullable();
                $table->text('error')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'whatsapp_number')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('whatsapp_number', 30)->nullable()->after('email');
            });
        }
    }

    protected function createMinimalPurchaseOrder(array $overrides = []): PurchaseOrder
    {
        $currencyId = (int) DB::table('currencies')->value('id');
        if (! $currencyId) {
            $currencyId = DB::table('currencies')->insertGetId([
                'code' => 'IDR',
                'name' => 'Rupiah Indonesia',
                'symbol' => 'Rp',
                'decimal_places' => 0,
                'is_active' => true,
                'is_base_currency' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $warehouseId = (int) DB::table('warehouses')->value('id');
        if (! $warehouseId) {
            $warehouseId = DB::table('warehouses')->insertGetId([
                'code' => 'WH-WA-'.uniqid(),
                'name' => 'Warehouse WA Test',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $entityId = (int) DB::table('company_entities')->where('code', '71')->value('id');
        if (! $entityId) {
            $entityId = DB::table('company_entities')->insertGetId([
                'code' => '71',
                'name' => 'PT Test Entity',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $vendorId = (int) DB::table('business_partners')
            ->where('partner_type', 'supplier')
            ->orderBy('id')
            ->value('id');
        if (! $vendorId) {
            $vendorId = DB::table('business_partners')->insertGetId([
                'code' => 'SUP-WA-'.uniqid(),
                'name' => 'Supplier WA Test',
                'partner_type' => 'supplier',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return PurchaseOrder::withoutEvents(function () use ($overrides, $currencyId, $warehouseId, $entityId, $vendorId) {
            return PurchaseOrder::query()->create(array_merge([
                'order_no' => 'PO-WA-'.uniqid(),
                'date' => now()->toDateString(),
                'business_partner_id' => $vendorId,
                'company_entity_id' => $entityId,
                'warehouse_id' => $warehouseId,
                'currency_id' => $currencyId,
                'order_type' => 'item',
                'status' => 'draft',
                'approval_status' => 'pending',
                'total_amount' => 100,
            ], $overrides));
        });
    }

    public function test_approve_command_approves_pending_purchase_order(): void
    {
        $user = User::factory()->create([
            'username' => 'wa-approve-'.uniqid(),
            'email' => 'wa-approve-'.uniqid().'@example.test',
            'whatsapp_number' => '628123456789',
        ]);

        $orderNo = 'PO-WA-APPROVE-'.uniqid();
        $po = $this->createMinimalPurchaseOrder(['order_no' => $orderNo]);

        PurchaseOrderApproval::query()->create([
            'purchase_order_id' => $po->id,
            'user_id' => $user->id,
            'approval_level' => 'manager',
            'status' => 'pending',
        ]);

        $reply = app(WhatsAppInboundHandler::class)->handle([
            'sender' => '08123456789',
            'body' => 'APPROVE '.$orderNo,
        ]);

        $this->assertStringContainsString('berhasil disetujui', $reply);

        $po->refresh();
        $this->assertSame('approved', $po->approval_status);
        $this->assertSame('ordered', $po->status);
    }

    public function test_reject_command_rejects_pending_purchase_order(): void
    {
        $user = User::factory()->create([
            'username' => 'wa-reject-'.uniqid(),
            'email' => 'wa-reject-'.uniqid().'@example.test',
            'whatsapp_number' => '628987654321',
        ]);

        $orderNo = 'PO-WA-REJECT-'.uniqid();
        $po = $this->createMinimalPurchaseOrder(['order_no' => $orderNo]);

        PurchaseOrderApproval::query()->create([
            'purchase_order_id' => $po->id,
            'user_id' => $user->id,
            'approval_level' => 'manager',
            'status' => 'pending',
        ]);

        $reply = app(WhatsAppInboundHandler::class)->handle([
            'sender' => '08987654321',
            'body' => 'REJECT '.$orderNo,
        ]);

        $this->assertStringContainsString('berhasil ditolak', $reply);

        $po->refresh();
        $this->assertSame('rejected', $po->approval_status);
    }

    public function test_unknown_sender_is_ignored(): void
    {
        $orderNo = 'PO-WA-UNKNOWN-'.uniqid();
        $po = $this->createMinimalPurchaseOrder(['order_no' => $orderNo]);

        $reply = app(WhatsAppInboundHandler::class)->handle([
            'sender' => '081111111111',
            'body' => 'APPROVE '.$orderNo,
        ]);

        $this->assertStringContainsString('Pengirim tidak dikenal', $reply);

        $po->refresh();
        $this->assertSame('pending', $po->approval_status);
        $this->assertSame('draft', $po->status);
    }

    public function test_invalid_purchase_order_number_returns_error_reply(): void
    {
        $phoneSuffix = str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        $whatsappNumber = '628'.$phoneSuffix;
        $senderLocal = '08'.$phoneSuffix;

        $user = User::factory()->create([
            'username' => 'wa-invalid-'.uniqid(),
            'email' => 'wa-invalid-'.uniqid().'@example.test',
            'whatsapp_number' => $whatsappNumber,
        ]);

        $reply = app(WhatsAppInboundHandler::class)->handle([
            'sender' => $senderLocal,
            'body' => 'APPROVE PO-NOT-EXISTS-999',
        ]);

        $this->assertStringContainsString('PO tidak ditemukan', $reply);
        $this->assertNotEmpty($user->id);
    }

    public function test_duplicate_gateway_message_id_is_skipped(): void
    {
        Http::fake([
            'api.fonnte.com/get-messages' => Http::response([
                'data' => [
                    [
                        'id' => 'fonnte-dup-001',
                        'sender' => '081234567890',
                        'message' => 'APPROVE PO-DUP-TEST',
                    ],
                ],
            ], 200),
            'api.fonnte.com/send' => Http::response([
                'status' => true,
                'id' => 'fonnte-out-001',
            ], 200),
        ]);

        WhatsAppMessage::query()->create([
            'direction' => 'in',
            'gateway_message_id' => 'fonnte-dup-001',
            'to_number' => '628111111111',
            'from_number' => '6281234567890',
            'body' => 'APPROVE PO-DUP-TEST',
            'message_type' => 'text',
            'status' => 'processed',
        ]);

        $this->artisan('whatsapp:poll-messages')->assertSuccessful();

        $this->assertSame(
            1,
            WhatsAppMessage::query()
                ->where('gateway_message_id', 'fonnte-dup-001')
                ->where('direction', 'in')
                ->count()
        );
    }
}
