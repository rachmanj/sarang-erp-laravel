<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('permissions')) {
            $now = now();
            $allPermissions = [
                // Period permissions
                'periods.view',
                'periods.close',

                // AR (Accounts Receivable) permissions
                'ar.invoices.view',
                'ar.invoices.create',
                'ar.invoices.post',
                'ar.receipts.view',
                'ar.receipts.create',
                'ar.receipts.post',

                // AP (Accounts Payable) permissions
                'ap.invoices.view',
                'ap.invoices.create',
                'ap.invoices.post',
                'ap.payments.view',
                'ap.payments.create',
                'ap.payments.post',
            ];

            foreach ($allPermissions as $permissionName) {
                if (!DB::table('permissions')->where('name', $permissionName)->exists()) {
                    DB::table('permissions')->insert([
                        'name' => $permissionName,
                        'guard_name' => 'web',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            // Grant all permissions to admin role if exists
            $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');
            if ($adminRoleId) {
                foreach ($allPermissions as $permissionName) {
                    $permissionId = DB::table('permissions')->where('name', $permissionName)->value('id');
                    if ($permissionId && !DB::table('role_has_permissions')->where(['role_id' => $adminRoleId, 'permission_id' => $permissionId])->exists()) {
                        DB::table('role_has_permissions')->insert([
                            'role_id' => $adminRoleId,
                            'permission_id' => $permissionId,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We do not delete permissions on down to avoid accidental privilege loss
    }
};
