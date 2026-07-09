<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarAccountingMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_accountant_sees_rekening_koran_in_sidebar(): void
    {
        $user = User::factory()->create();
        $user->assignRole('accountant');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Rekening Koran', false);
    }

    public function test_approver_sees_rekening_koran_in_sidebar(): void
    {
        $user = User::factory()->create();
        $user->assignRole('approver');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Rekening Koran', false);
    }

    public function test_user_without_bank_permissions_does_not_see_rekening_koran(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('reports.view');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Rekening Koran', false);
    }
}
