<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add permissions for price types
        $permissions = [
            'view-price-types',
            'create-price-types',
            'edit-price-types',
            'delete-price-types'
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assign permissions to the admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }

        // Assign view permission to other roles that can view products
        $roles = Role::whereNot('name', 'admin')
            ->whereHas('permissions', function ($query) {
                $query->where('name', 'view-products');
            })
            ->get();

        foreach ($roles as $role) {
            $role->givePermissionTo('view-price-types');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove permissions
        $permissions = [
            'view-price-types',
            'create-price-types',
            'edit-price-types',
            'delete-price-types'
        ];

        foreach ($permissions as $permission) {
            $permission = Permission::where('name', $permission)->first();
            if ($permission) {
                $permission->delete();
            }
        }
    }
};
