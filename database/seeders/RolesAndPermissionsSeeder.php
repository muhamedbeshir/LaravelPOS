<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // إنشاء الصلاحيات - فئة إدارة المستخدمين
        $userManagementPermissions = [
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'manage-users',
            'assign-roles',
        ];

        // إنشاء الصلاحيات - فئة المنتجات
        $productPermissions = [
            'view-products',
            'create-products',
            'edit-products',
            'delete-products',
            'import-products',
            'export-products',
            'print-barcode',
        ];

        // إنشاء الصلاحيات - فئة المجموعات
        $categoryPermissions = [
            'view-categories',
            'create-categories',
            'edit-categories',
            'delete-categories',
        ];

        // إنشاء الصلاحيات - فئة الوحدات
        $unitPermissions = [
            'view-units',
            'create-units',
            'edit-units',
            'delete-units',
        ];

        // إنشاء الصلاحيات - فئة المبيعات
        $salesPermissions = [
            'view-sales',
            'create-sales',
            'edit-sales',
            'delete-sales',
            'manage-sale-payments',
            'pos',
        ];

        // إنشاء الصلاحيات - فئة المشتريات
        $purchasePermissions = [
            'view-purchases',
            'create-purchases',
            'edit-purchases',
            'delete-purchases',
            'manage-purchase-payments',
        ];

        // إنشاء الصلاحيات - فئة المخزون
        $inventoryPermissions = [
            'view-inventory',
            'manage-inventory',
            'adjust-inventory',
        ];

        // إنشاء الصلاحيات - فئة العملاء
        $customerPermissions = [
            'view-customers',
            'create-customers',
            'edit-customers',
            'delete-customers',
        ];

        // إنشاء الصلاحيات - فئة الموردين
        $supplierPermissions = [
            'view-suppliers',
            'create-suppliers',
            'edit-suppliers',
            'delete-suppliers',
        ];

        // إنشاء الصلاحيات - فئة الموظفين
        $employeePermissions = [
            'view-employees',
            'create-employees',
            'edit-employees',
            'delete-employees',
        ];

        // إنشاء الصلاحيات - فئة المصروفات
        $expensePermissions = [
            'view-expenses',
            'create-expenses',
            'edit-expenses',
            'delete-expenses',
        ];

        // إنشاء الصلاحيات - فئة الإيرادات (الودائع)
        $depositPermissions = [
            'view-deposits',
            'create-deposits',
            'edit-deposits',
            'delete-deposits',
        ];

        // إنشاء الصلاحيات - فئة فئات المصروفات
        $expenseCategoryPermissions = [
            'view-expense-categories',
            'create-expense-categories',
            'edit-expense-categories',
            'delete-expense-categories',
        ];

        // إنشاء الصلاحيات - فئة مصادر الإيداع
        $depositSourcePermissions = [
            'view-deposit-sources',
            'create-deposit-sources',
            'edit-deposit-sources',
            'delete-deposit-sources',
        ];

        // إنشاء الصلاحيات - فئة المسميات الوظيفية
        $jobTitlePermissions = [
            'view-job-titles',
            'create-job-titles',
            'edit-job-titles',
            'delete-job-titles',
        ];

        // إنشاء الصلاحيات - فئة التقارير
        $reportPermissions = [
            'view-sales-report',
            'view-purchases-report',
            'view-customers-report',
            'view-suppliers-report',
            'view-inventory-report',
            'view-employees-report',
            'export-reports',
        ];

        // إنشاء الصلاحيات - فئة الإعدادات
        $settingPermissions = [
            'view-settings',
            'edit-settings',
        ];

        // إنشاء الصلاحيات - فئة لوحة التحكم
        $dashboardPermissions = [
            'access-dashboard',
        ];

        // إنشاء الصلاحيات - فئة النسخ الاحتياطي
        $backupPermissions = [
            'view-backups',
            'create-backups',
            'download-backups',
            'delete-backups',
        ];

        // إنشاء الصلاحيات - فئة الورديات
        $shiftPermissions = [
            'view-shifts',
            'create-shifts',
            'edit-shifts',
            'delete-shifts',
        ];

        // تجميع كل الصلاحيات
        $allPermissions = array_merge(
            $userManagementPermissions,
            $productPermissions,
            $categoryPermissions,
            $unitPermissions,
            $salesPermissions,
            $purchasePermissions,
            $inventoryPermissions,
            $customerPermissions,
            $supplierPermissions,
            $employeePermissions,
            $jobTitlePermissions,
            $reportPermissions,
            $settingPermissions,
            $dashboardPermissions,
            $backupPermissions,
            $shiftPermissions,
            $expensePermissions,
            $depositPermissions,
            $expenseCategoryPermissions,
            $depositSourcePermissions
        );

        // إنشاء كل الصلاحيات في قاعدة البيانات
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // إنشاء مجموعات الصلاحيات المجمعة
        $productGroup = [
            'name' => 'إدارة المنتجات',
            'permissions' => array_merge($productPermissions, $categoryPermissions, $unitPermissions)
        ];

        $salesGroup = [
            'name' => 'إدارة المبيعات',
            'permissions' => array_merge($salesPermissions, $customerPermissions)
        ];

        $purchaseGroup = [
            'name' => 'إدارة المشتريات',
            'permissions' => array_merge($purchasePermissions, $supplierPermissions, $inventoryPermissions)
        ];

        $peopleGroup = [
            'name' => 'إدارة الأشخاص',
            'permissions' => array_merge($employeePermissions, $jobTitlePermissions, $customerPermissions, $supplierPermissions)
        ];

        $reportGroup = [
            'name' => 'إدارة التقارير',
            'permissions' => $reportPermissions
        ];

        $systemGroup = [
            'name' => 'إدارة النظام',
            'permissions' => array_merge($userManagementPermissions, $settingPermissions, $backupPermissions, $dashboardPermissions)
        ];

        $shiftGroup = [
            'name' => 'إدارة الورديات',
            'permissions' => $shiftPermissions
        ];

        $expenseGroup = [
            'name' => 'إدارة المصروفات',
            'permissions' => array_merge($expensePermissions, $expenseCategoryPermissions)
        ];

        $depositGroup = [
            'name' => 'إدارة الإيرادات',
            'permissions' => array_merge($depositPermissions, $depositSourcePermissions)
        ];

        // إنشاء الأدوار
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo($allPermissions);

        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $managerRole->givePermissionTo(array_merge(
            $productGroup['permissions'],
            $salesGroup['permissions'],
            $purchaseGroup['permissions'],
            $reportGroup['permissions'],
            $dashboardPermissions,
            ['view-shifts'],
            $expenseGroup['permissions'],
            $depositGroup['permissions']
        ));

        $salesSupervisorRole = Role::firstOrCreate(['name' => 'sales-supervisor']);
        $salesSupervisorRole->givePermissionTo(array_merge(
            $salesGroup['permissions'],
            ['view-products', 'view-categories'],
            ['view-sales-report', 'view-customers-report'],
            $dashboardPermissions,
            ['view-shifts'] // Give sales-supervisor view-shifts
        ));

        $salesStaffRole = Role::firstOrCreate(['name' => 'sales-staff']);
        $salesStaffRole->givePermissionTo([
            'view-products', 'pos', 'view-sales', 'create-sales',
            'view-customers', 'create-customers', 'edit-customers',
            $dashboardPermissions[0]
        ]);

        $inventorySupervisorRole = Role::firstOrCreate(['name' => 'inventory-supervisor']);
        $inventorySupervisorRole->givePermissionTo(array_merge(
            $inventoryPermissions,
            $productPermissions,
            $categoryPermissions,
            ['view-purchases', 'view-suppliers'],
            ['view-inventory-report'],
            $dashboardPermissions
        ));

        $stockWorkerRole = Role::firstOrCreate(['name' => 'stock-worker']);
        $stockWorkerRole->givePermissionTo([
            'view-products', 'view-categories',
            'view-inventory', 'adjust-inventory',
            $dashboardPermissions[0]
        ]);

        $accountantRole = Role::firstOrCreate(['name' => 'accountant']);
        $accountantRole->givePermissionTo(array_merge(
            ['view-sales', 'view-purchases', 'view-customers', 'view-suppliers'],
            $reportPermissions,
            $dashboardPermissions,
            $expenseGroup['permissions'],
            $depositGroup['permissions']
        ));

        // إنشاء مستخدم المدير إذا لم يكن موجوداً
        $admin = \App\Models\User::where('email', 'admin@admin.com')->first();

        if (!$admin) {
            $admin = \App\Models\User::create([
                'name' => 'المدير',
                'email' => 'admin@admin.com',
                'username' => 'admin',
                'password' => 'admin'  // Store without hashing
            ]);
        } else {
            // تحديث كلمة المرور في حالة وجود المستخدم
            $admin->password = 'admin';  // Store without hashing
            $admin->username = 'admin';
            $admin->save();
        }
        
        $admin->assignRole('admin');
    }
}
