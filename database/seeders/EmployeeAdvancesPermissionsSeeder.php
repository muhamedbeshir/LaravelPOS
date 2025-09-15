<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EmployeeAdvancesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء أذونات السلف
        $permissions = [
            'view-employee-advances',
            'create-employee-advances',
            'edit-employee-advances',
            'delete-employee-advances',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // إضافة الأذونات للمدير
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }

        // إضافة الأذونات للمحاسب
        $accountantRole = Role::where('name', 'accountant')->first();
        if ($accountantRole) {
            $accountantRole->givePermissionTo([
                'view-employee-advances',
                'create-employee-advances',
                'edit-employee-advances',
            ]);
        }

        // إضافة الأذونات لمدير الموارد البشرية
        $hrRole = Role::where('name', 'hr_manager')->first();
        if ($hrRole) {
            $hrRole->givePermissionTo($permissions);
        }

        $this->command->info('تم إضافة أذونات السلف بنجاح');
    }
}
