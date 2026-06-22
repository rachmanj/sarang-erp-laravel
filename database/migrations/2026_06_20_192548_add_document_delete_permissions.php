<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /** @var list<string> */
    private array $permissions = [
        'ar.invoices.delete',
        'ar.receipts.delete',
        'ar.credit-memos.delete',
        'ap.invoices.delete',
        'ap.payments.delete',
        'goods-receipt-pos.delete',
        'delivery-orders.delete',
    ];

    public function up(): void
    {
        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $superadmin = Role::query()->where('name', 'superadmin')->first();
        if ($superadmin) {
            $superadmin->givePermissionTo($this->permissions);
        }
    }

    public function down(): void
    {
        $superadmin = Role::query()->where('name', 'superadmin')->first();
        if ($superadmin) {
            $superadmin->revokePermissionTo($this->permissions);
        }

        foreach ($this->permissions as $permission) {
            Permission::where('name', $permission)->delete();
        }
    }
};
