<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddPriceTypePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create price-types permissions
        $permissions = [
            'view-price-types',
            'create-price-types',
            'edit-price-types',
            'delete-price-types',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
            $this->command->info("Created permission: {$permission}");
        }

        // Add permissions to admin role
        $adminRole = Role::where('name', 'admin')->first();
        
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
            $this->command->info('Added price-types permissions to admin role');
        } else {
            $this->command->warn('Admin role not found');
        }

        // Add view permission to manager role
        $managerRole = Role::where('name', 'manager')->first();
        
        if ($managerRole) {
            $managerRole->givePermissionTo(['view-price-types', 'edit-price-types']);
            $this->command->info('Added view/edit price-types permissions to manager role');
        }

        $this->command->info('Price types permissions have been added successfully');
    }
}
