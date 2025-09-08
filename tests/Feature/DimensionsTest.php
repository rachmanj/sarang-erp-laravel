<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DimensionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed();
    }

    public function test_projects_page_requires_permission(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        $this->get('/projects')->assertStatus(403);
    }

    public function test_admin_can_view_and_create_project(): void
    {
        $admin = \App\Models\User::first();
        $this->actingAs($admin);
        $this->get('/projects')->assertStatus(200);
        $this->post('/projects', [
            'code' => 'PRJ-TST',
            'name' => 'Test Project',
            'budget_total' => 1000,
        ])->assertStatus(302);
        $this->assertDatabaseHas('projects', ['code' => 'PRJ-TST']);
    }

    public function test_delete_blocked_when_project_used(): void
    {
        $admin = \App\Models\User::first();
        $this->actingAs($admin);
        $pid = \Illuminate\Support\Facades\DB::table('projects')->insertGetId([
            'code' => 'PRJ-BLK',
            'name' => 'Blocked',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        \Illuminate\Support\Facades\DB::table('journals')->insert(['date' => now()->toDateString(), 'source_type' => 't', 'source_id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $jid = \Illuminate\Support\Facades\DB::getPdo()->lastInsertId();
        \Illuminate\Support\Facades\DB::table('journal_lines')->insert([
            'journal_id' => $jid,
            'account_id' => \Illuminate\Support\Facades\DB::table('accounts')->value('id'),
            'debit' => 1,
            'credit' => 0,
            'project_id' => $pid,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $this->delete('/projects/' . $pid)->assertStatus(422);
    }
}
