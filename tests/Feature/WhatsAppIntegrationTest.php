<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppMessage;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\WhatsApp\FonnteGateway;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WhatsAppIntegrationTest extends TestCase
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

        if (! Schema::hasTable('whatsapp_messages')) {
            Schema::create('whatsapp_messages', function ($table) {
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
    }

    public function test_whatsapp_service_is_disabled_when_module_off(): void
    {
        Config::set('whatsapp.module_enabled', false);

        $service = app(WhatsAppService::class);

        $this->assertFalse($service->isEnabled());
    }

    public function test_whatsapp_service_is_disabled_after_expiry(): void
    {
        Config::set('whatsapp.expiry', now()->subDay()->toDateString());

        $service = app(WhatsAppService::class);

        $this->assertFalse($service->isEnabled());
    }

    public function test_send_whatsapp_message_job_logs_successful_outbound_message(): void
    {
        Http::fake([
            'api.fonnte.com/send' => Http::response([
                'status' => true,
                'id' => 'fonnte-msg-123',
            ], 200),
        ]);

        $job = new SendWhatsAppMessage('628333333333', 'Halo uji coba', 'test');
        $job->handle(app(FonnteGateway::class));

        $this->assertDatabaseHas('whatsapp_messages', [
            'direction' => 'out',
            'to_number' => '628333333333',
            'from_number' => '628111111111',
            'body' => 'Halo uji coba',
            'message_type' => 'test',
            'status' => 'sent',
            'gateway_message_id' => 'fonnte-msg-123',
        ]);
    }

    public function test_notify_purchase_order_pending_approval_dispatches_job(): void
    {
        Bus::fake();

        $purchaseOrder = new PurchaseOrder([
            'order_no' => 'PO-WA-TEST-001',
            'total_amount' => 1500000,
            'approval_status' => 'pending',
        ]);
        $purchaseOrder->id = 99;

        app(WhatsAppService::class)->notifyPurchaseOrderPendingApproval($purchaseOrder);

        Bus::assertDispatched(SendWhatsAppMessage::class, function (SendWhatsAppMessage $job) {
            return $job->to === '628222222222'
                && str_contains($job->message, 'PO-WA-TEST-001')
                && str_contains($job->message, 'APPROVE PO-WA-TEST-001')
                && str_contains($job->message, 'REJECT PO-WA-TEST-001')
                && $job->relatedEntityType === PurchaseOrder::class
                && $job->relatedEntityId === 99;
        });
    }

    public function test_settings_page_requires_permission(): void
    {
        Permission::findOrCreate('whatsapp.settings');

        $user = User::factory()->create([
            'username' => 'no-wa-'.uniqid(),
            'email' => 'no-wa-'.uniqid().'@example.test',
        ]);

        $this->actingAs($user)
            ->get(route('whatsapp.settings.edit'))
            ->assertForbidden();
    }

    public function test_settings_page_loads_for_authorized_user(): void
    {
        Permission::findOrCreate('whatsapp.settings');

        $user = User::factory()->create([
            'username' => 'wa-admin-'.uniqid(),
            'email' => 'wa-admin-'.uniqid().'@example.test',
        ]);
        $user->givePermissionTo('whatsapp.settings');

        $this->actingAs($user)
            ->get(route('whatsapp.settings.edit'))
            ->assertOk()
            ->assertSee('Pengaturan WhatsApp')
            ->assertSee('Kirim Pesan Uji Coba');
    }
}
